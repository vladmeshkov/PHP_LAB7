<?php
declare(strict_types=1);

namespace Hotel\Repositories;

use PDO;

/** Доступ к таблице bookings и сводная статистика для админ-панели. */
final class BookingRepository
{
    private const SELECT = "SELECT b.*, r.number AS room_number, r.class AS room_class, r.price AS room_price,
                                   u.name AS user_name, u.email AS user_email,
                                   p.amount AS amount, p.method AS pay_method, p.status AS pay_status
                            FROM bookings b
                            JOIN rooms r ON r.id = b.room_id
                            JOIN users u ON u.id = b.user_id
                            LEFT JOIN payments p ON p.booking_id = b.id";

    public function __construct(private PDO $pdo)
    {
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(self::SELECT . ' WHERE b.id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public function create(int $userId, int $roomId, string $from, string $to, int $guests): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO bookings (user_id, room_id, date_from, date_to, guests, status)
                                     VALUES (:user, :room, :from, :to, :guests, 'confirmed')");
        $stmt->execute(['user' => $userId, 'room' => $roomId, 'from' => $from, 'to' => $to, 'guests' => $guests]);

        return (int) $this->pdo->lastInsertId();
    }

    public function cancel(int $id): void
    {
        $this->pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = :id")->execute(['id' => $id]);
    }

    /** Есть ли подтверждённая бронь номера, пересекающаяся с периодом [from, to). */
    public function hasOverlap(int $roomId, string $from, string $to): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM bookings WHERE room_id = :room AND status = 'confirmed'
                                     AND date_from < :to AND date_to > :from LIMIT 1");
        $stmt->execute(['room' => $roomId, 'from' => $from, 'to' => $to]);

        return $stmt->fetchColumn() !== false;
    }

    public function hasAnyForRoom(int $roomId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM bookings WHERE room_id = :room LIMIT 1');
        $stmt->execute(['room' => $roomId]);

        return $stmt->fetchColumn() !== false;
    }

    /** Занятые периоды номера (для подсказки на странице номера). */
    public function upcomingForRoom(int $roomId, int $limit = 6): array
    {
        $stmt = $this->pdo->prepare("SELECT date_from, date_to FROM bookings WHERE room_id = :room AND status = 'confirmed'
                                     AND date_to >= CURDATE() ORDER BY date_from LIMIT :limit");
        $stmt->bindValue('room', $roomId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function countForUser(int $userId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM bookings WHERE user_id = :user');
        $stmt->execute(['user' => $userId]);

        return (int) $stmt->fetchColumn();
    }

    public function pageForUser(int $userId, int $limit, int $offset): array
    {
        $stmt = $this->pdo->prepare(self::SELECT . ' WHERE b.user_id = :user ORDER BY b.date_from DESC, b.id DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue('user', $userId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** Условие для админ-списка: статус и поиск по клиенту или номеру комнаты. */
    private function adminWhere(string $status, string $search): array
    {
        $conds = ['1 = 1'];
        $params = [];
        if ($status !== '') {
            $conds[] = 'b.status = :status';
            $params['status'] = $status;
        }
        if ($search !== '') {
            $like = '%' . addcslashes($search, '%_\\') . '%';
            $conds[] = '(u.name LIKE :s1 OR u.email LIKE :s2 OR r.number LIKE :s3)';
            $params += ['s1' => $like, 's2' => $like, 's3' => $like];
        }

        return ['WHERE ' . implode(' AND ', $conds), $params];
    }

    public function countAll(string $status, string $search): int
    {
        [$where, $params] = $this->adminWhere($status, $search);
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM bookings b JOIN rooms r ON r.id = b.room_id JOIN users u ON u.id = b.user_id ' . $where);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function pageAll(string $status, string $search, int $limit, int $offset): array
    {
        [$where, $params] = $this->adminWhere($status, $search);
        $stmt = $this->pdo->prepare(self::SELECT . " {$where} ORDER BY b.id DESC LIMIT :limit OFFSET :offset");
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    // ---------- Статистика для админ-панели ----------

    /** @return array{users: int, rooms: int, active: int, revenue: string, occupancy: float} */
    public function summary(int $days = 30): array
    {
        $users = (int) $this->pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $rooms = (int) $this->pdo->query('SELECT COUNT(*) FROM rooms WHERE is_active = 1')->fetchColumn();
        $active = (int) $this->pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed' AND date_to >= CURDATE()")->fetchColumn();
        $revenue = (string) $this->pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'paid'")->fetchColumn();

        // Загрузка: занятые ночи в ближайшие $days дней / все возможные ночи активных номеров
        $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(DATEDIFF(LEAST(b.date_to, DATE_ADD(CURDATE(), INTERVAL :d1 DAY)), GREATEST(b.date_from, CURDATE()))), 0)
                                     FROM bookings b JOIN rooms r ON r.id = b.room_id
                                     WHERE b.status = 'confirmed' AND r.is_active = 1
                                       AND b.date_from < DATE_ADD(CURDATE(), INTERVAL :d2 DAY) AND b.date_to > CURDATE()");
        $stmt->execute(['d1' => $days, 'd2' => $days]);
        $nights = (float) $stmt->fetchColumn();
        $capacity = $rooms * $days;

        return ['users' => $users, 'rooms' => $rooms, 'active' => $active, 'revenue' => $revenue,
                'occupancy' => $capacity > 0 ? round(100 * $nights / $capacity, 1) : 0.0];
    }

    /** Номера, брони и выручка по классам. */
    public function byClass(): array
    {
        return $this->pdo->query("SELECT r.class,
                                         COUNT(DISTINCT r.id) AS rooms,
                                         COUNT(b.id) AS bookings,
                                         COALESCE(SUM(CASE WHEN p.status = 'paid' THEN p.amount END), 0) AS revenue
                                  FROM rooms r
                                  LEFT JOIN bookings b ON b.room_id = r.id AND b.status = 'confirmed'
                                  LEFT JOIN payments p ON p.booking_id = b.id
                                  GROUP BY r.class
                                  ORDER BY FIELD(r.class, 'economy', 'standard', 'comfort', 'suite')")->fetchAll();
    }

    public function byStatus(): array
    {
        return $this->pdo->query('SELECT status, COUNT(*) AS total FROM bookings GROUP BY status')->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public function latest(int $limit = 6): array
    {
        $stmt = $this->pdo->prepare(self::SELECT . ' ORDER BY b.id DESC LIMIT :limit');
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
