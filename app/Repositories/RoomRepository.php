<?php
declare(strict_types=1);

namespace Hotel\Repositories;

use PDO;

/** Доступ к таблице rooms: каталог с фильтрами, проверка доступности на даты, CRUD для администратора. */
final class RoomRepository
{
    /** Классы номеров: код в БД => название на сайте. */
    public const CLASSES = ['economy' => 'Эконом', 'standard' => 'Стандарт', 'comfort' => 'Комфорт', 'suite' => 'Люкс'];

    private const SORTS = [
        'number'     => 'r.number ASC',
        'price_asc'  => 'r.price ASC, r.number ASC',
        'price_desc' => 'r.price DESC, r.number ASC',
    ];

    public function __construct(private PDO $pdo)
    {
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM rooms WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public function findByNumber(string $number): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM rooms WHERE number = :number');
        $stmt->execute(['number' => $number]);

        return $stmt->fetch() ?: null;
    }

    /**
     * Условия отбора номеров. Фильтры:
     *  class       — класс номера;
     *  guests      — минимальная вместимость;
     *  from, to    — оставить только номера, свободные на эти даты;
     *  search      — часть номера (для админ-панели);
     *  only_active — скрыть выведенные из эксплуатации номера.
     *
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function where(array $f): array
    {
        $conds = ['1 = 1'];
        $params = [];

        if (!empty($f['only_active'])) {
            $conds[] = 'r.is_active = 1';
        }
        if (!empty($f['class'])) {
            $conds[] = 'r.class = :class';
            $params['class'] = $f['class'];
        }
        if (!empty($f['guests'])) {
            $conds[] = 'r.capacity >= :guests';
            $params['guests'] = (int) $f['guests'];
        }
        if (!empty($f['search'])) {
            $conds[] = 'r.number LIKE :search';
            $params['search'] = '%' . addcslashes((string) $f['search'], '%_\\') . '%';
        }
        if (!empty($f['from']) && !empty($f['to'])) {
            // Номер свободен, если нет подтверждённой брони, пересекающейся с выбранными датами.
            // День выезда свободен для заезда следующего гостя, поэтому сравнение строгое.
            $conds[] = "NOT EXISTS (SELECT 1 FROM bookings b
                                    WHERE b.room_id = r.id AND b.status = 'confirmed'
                                      AND b.date_from < :to AND b.date_to > :from)";
            $params['from'] = $f['from'];
            $params['to'] = $f['to'];
        }

        return ['WHERE ' . implode(' AND ', $conds), $params];
    }

    public function count(array $filters): int
    {
        [$where, $params] = $this->where($filters);
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM rooms r ' . $where);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function page(array $filters, string $sort, int $limit, int $offset): array
    {
        [$where, $params] = $this->where($filters);
        // Порядок сортировки берётся из белого списка: пользовательская строка в ORDER BY не попадает.
        $order = self::SORTS[$sort] ?? self::SORTS['number'];

        $stmt = $this->pdo->prepare("SELECT r.* FROM rooms r {$where} ORDER BY {$order} LIMIT :limit OFFSET :offset");
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function create(array $d): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO rooms (number, class, price, capacity, description, is_active)
                                     VALUES (:number, :class, :price, :capacity, :description, :is_active)');
        $stmt->execute($this->fields($d));

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $d): void
    {
        $stmt = $this->pdo->prepare('UPDATE rooms SET number = :number, class = :class, price = :price, capacity = :capacity,
                                     description = :description, is_active = :is_active WHERE id = :id');
        $stmt->execute($this->fields($d) + ['id' => $id]);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare('DELETE FROM rooms WHERE id = :id')->execute(['id' => $id]);
    }

    public function total(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM rooms')->fetchColumn();
    }

    private function fields(array $d): array
    {
        return [
            'number'      => $d['number'],
            'class'       => $d['class'],
            'price'       => str_replace(',', '.', (string) $d['price']),
            'capacity'    => (int) $d['capacity'],
            'description' => $d['description'] !== '' ? $d['description'] : null,
            'is_active'   => !empty($d['is_active']) ? 1 : 0,
        ];
    }
}
