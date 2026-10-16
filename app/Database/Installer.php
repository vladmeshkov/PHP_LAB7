<?php
declare(strict_types=1);

namespace Hotel\Database;

use PDO;

/** Создание таблиц из database/schema.sql и заполнение тестовыми данными. */
final class Installer
{
    private const FIRST = ['Александр', 'Мария', 'Дмитрий', 'Анна', 'Сергей', 'Екатерина', 'Андрей', 'Ольга', 'Павел', 'Наталья', 'Игорь', 'Виктория'];
    private const LAST = ['Иванов', 'Петрова', 'Козлов', 'Новик', 'Сидоренко', 'Лукашевич', 'Ковалёв', 'Мельник', 'Шинкевич', 'Бондарь', 'Романов', 'Кравец'];
    private const TRANSLIT = ['а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y',
        'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h',
        'ц' => 'c', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ы' => 'y', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya', 'ь' => '', 'ъ' => ''];

    private const CLASS_DATA = [
        'economy'  => ['price' => [55, 70],   'capacity' => [1, 2], 'text' => 'Компактный номер с односпальными кроватями, душем и Wi-Fi. Подходит для коротких поездок.'],
        'standard' => ['price' => [85, 110],  'capacity' => [2, 3], 'text' => 'Просторный номер с двуспальной кроватью, рабочим столом, телевизором и кондиционером.'],
        'comfort'  => ['price' => [130, 170], 'capacity' => [2, 4], 'text' => 'Улучшенный номер с видом на город, мини-баром, ванной комнатой и зоной отдыха.'],
        'suite'    => ['price' => [250, 350], 'capacity' => [4, 6], 'text' => 'Двухкомнатный люкс с гостиной, кабинетом, ванной с джакузи и панорамными окнами.'],
    ];

    public const ADMIN_EMAIL = 'admin@hotel.by';
    public const ADMIN_PASSWORD = 'Admin123';
    public const CLIENT_PASSWORD = 'Client123';

    public function __construct(private PDO $pdo, private string $root)
    {
    }

    /** Создаёт таблицы. При $fresh сначала удаляет существующие. */
    public function createSchema(bool $fresh = false): void
    {
        if ($fresh) {
            $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
            foreach (['payments', 'bookings', 'rooms', 'users'] as $table) {
                $this->pdo->exec("DROP TABLE IF EXISTS {$table}");
            }
            $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        }

        $sql = (string) file_get_contents($this->root . '/database/schema.sql');
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
            $this->pdo->exec($statement);
        }
    }

    public function isEmpty(): bool
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0;
    }

    /**
     * Тестовые данные: 12 пользователей (1 администратор), 60 номеров, около 150 бронирований с оплатами.
     * Даты считаются от сегодняшнего дня, поэтому в каталоге всегда есть и прошлые, и будущие брони.
     *
     * @return array{users: int, rooms: int, bookings: int}
     */
    public function seed(): array
    {
        mt_srand(2026);
        $this->pdo->beginTransaction();

        // --- Пользователи ---
        $insertUser = $this->pdo->prepare('INSERT INTO users (name, email, password_hash, role, created_at) VALUES (:name, :email, :hash, :role, :created)');
        $insertUser->execute(['name' => 'Администратор', 'email' => self::ADMIN_EMAIL, 'hash' => password_hash(self::ADMIN_PASSWORD, PASSWORD_DEFAULT),
            'role' => 'admin', 'created' => date('Y-m-d H:i:s', strtotime('-90 days'))]);

        $clientHash = password_hash(self::CLIENT_PASSWORD, PASSWORD_DEFAULT);
        $clientIds = [];
        for ($i = 0; $i < 11; $i++) {
            $first = self::FIRST[$i];
            $last = self::LAST[$i];
            $insertUser->execute([
                'name'    => $first . ' ' . $last,
                'email'   => $this->translit($first) . '.' . $this->translit($last) . '@example.by',
                'hash'    => $clientHash,
                'role'    => 'user',
                'created' => date('Y-m-d H:i:s', strtotime('-' . mt_rand(10, 80) . ' days')),
            ]);
            $clientIds[] = (int) $this->pdo->lastInsertId();
        }

        // --- Номера: 6 этажей по 10 номеров ---
        $insertRoom = $this->pdo->prepare('INSERT INTO rooms (number, class, price, capacity, description, is_active) VALUES (:number, :class, :price, :capacity, :description, :active)');
        $rooms = [];
        for ($floor = 1; $floor <= 6; $floor++) {
            for ($i = 1; $i <= 10; $i++) {
                $class = $i <= 3 ? 'economy' : ($i <= 6 ? 'standard' : ($i <= 9 ? 'comfort' : 'suite'));
                $data = self::CLASS_DATA[$class];
                $price = mt_rand($data['price'][0], $data['price'][1]);
                $capacity = mt_rand($data['capacity'][0], $data['capacity'][1]);
                $number = (string) ($floor * 100 + $i);
                $insertRoom->execute([
                    'number' => $number, 'class' => $class, 'price' => $price, 'capacity' => $capacity,
                    'description' => $data['text'] . " Этаж {$floor}.",
                    'active' => in_array($number, ['105', '408'], true) ? 0 : 1,   // два номера сняты с продажи
                ]);
                $rooms[] = ['id' => (int) $this->pdo->lastInsertId(), 'price' => $price, 'capacity' => $capacity, 'active' => !in_array($number, ['105', '408'], true)];
            }
        }

        // --- Бронирования: для каждого номера цепочка непересекающихся периодов от -60 до +60 дней ---
        // Статусы согласованы с датами: прошедшие – завершены (есть отмены и «не заехал»), текущие – проживают,
        // будущие – оплачены, ждут оплаты или отменены.
        $insertBooking = $this->pdo->prepare('INSERT INTO bookings (user_id, room_id, date_from, date_to, guests, status, cancel_reason, created_at)
                                              VALUES (:user, :room, :from, :to, :guests, :status, :reason, :created)');
        $insertPayment = $this->pdo->prepare('INSERT INTO payments (booking_id, amount, method, status, due_at, paid_at)
                                              VALUES (:booking, :amount, :method, :status, :due, :paid)');
        $methods = ['card', 'online', 'cash'];
        $today = strtotime('today');
        $now = time();
        $count = 0;
        $dt = static fn (int $ts): string => date('Y-m-d H:i:s', $ts);

        foreach ($rooms as $room) {
            $cursor = $today + mt_rand(-60, -20) * 86400;
            while ($cursor < $today + 60 * 86400) {
                $nights = mt_rand(1, 7);
                $from = $cursor;
                $to = $cursor + $nights * 86400;
                $cursor = $to + mt_rand(0, 9) * 86400;

                if (mt_rand(1, 100) > 40) {
                    continue;   // не каждый промежуток занят
                }

                $method = $methods[array_rand($methods)];
                $created = min($now - 3600, $from - mt_rand(1, 20) * 86400 + mt_rand(28800, 72000));
                $reason = null;
                $roll = mt_rand(1, 100);

                if ($to < $today) {                       // проживание закончилось
                    $status = $roll <= 82 ? 'completed' : ($roll <= 90 ? 'cancelled' : 'no_show');
                } elseif ($from < $today) {               // сейчас живёт
                    $status = 'checked_in';
                } elseif ($roll <= 15) {                  // будущая бронь, отменена
                    $status = 'cancelled';
                } elseif ($roll <= 70) {
                    $status = 'paid';
                } else {
                    $status = 'booked';                   // ждёт оплаты
                }

                // Оплата по статусу брони
                $payStatus = 'paid';
                $due = null;
                $paidAt = $dt($created + 600);
                if ($status === 'booked') {
                    $payStatus = 'pending';
                    $paidAt = null;
                    if ($method !== 'cash') {             // карта и онлайн: срок ещё не вышел
                        $created = $now - mt_rand(1, 20) * 3600;
                        $due = $dt($created + (int) config('payment_hold_hours', 24) * 3600);
                    }
                } elseif ($status === 'cancelled') {
                    $reason = mt_rand(0, 1) ? 'client' : 'admin';
                    $wasPaid = mt_rand(0, 1) === 1;
                    $payStatus = $wasPaid ? 'refunded' : 'cancelled';
                    $paidAt = $wasPaid ? $paidAt : null;
                }

                $insertBooking->execute([
                    'user' => $clientIds[array_rand($clientIds)], 'room' => $room['id'],
                    'from' => date('Y-m-d', $from), 'to' => date('Y-m-d', $to),
                    'guests' => mt_rand(1, $room['capacity']), 'status' => $status, 'reason' => $reason, 'created' => $dt($created),
                ]);
                $insertPayment->execute([
                    'booking' => (int) $this->pdo->lastInsertId(), 'amount' => number_format($room['price'] * $nights, 2, '.', ''),
                    'method' => $method, 'status' => $payStatus, 'due' => $due, 'paid' => $paidAt,
                ]);
                $count++;
            }
        }

        $this->pdo->commit();

        return ['users' => 12, 'rooms' => count($rooms), 'bookings' => $count];
    }

    private function translit(string $word): string
    {
        return strtr(mb_strtolower($word), self::TRANSLIT);
    }
}
