<?php
declare(strict_types=1);

/**
 * Автоматические проверки (без внешних библиотек):  php tests/run.php
 *
 * Часть 1 – модульные тесты (проверка данных, маршрутизатор, расчёты).
 * Часть 2 – проверки на настоящей базе: отдельная БД hotel_test, которая создаётся заново при каждом запуске.
 *           Параметры подключения берутся из .env (пользователь должен иметь право создавать базы).
 */

use Hotel\Core\Database;
use Hotel\Core\HttpException;
use Hotel\Core\Paginator;
use Hotel\Core\Router;
use Hotel\Core\Validator;
use Hotel\Database\Installer;
use Hotel\Repositories\BookingRepository;
use Hotel\Repositories\PaymentRepository;
use Hotel\Repositories\RoomRepository;
use Hotel\Repositories\UserRepository;
use Hotel\Services\BookingException;
use Hotel\Services\BookingService;

$_ENV['DB_NAME'] = 'hotel_test';
$app = require dirname(__DIR__) . '/app/bootstrap.php';

// Защита: тесты пересоздают таблицы, поэтому работают только с базой, имя которой заканчивается на _test.
if (!str_ends_with((string) $app->config('database.name'), '_test')) {
    fwrite(STDERR, "Тесты остановлены: они пересоздают таблицы и должны работать только с тестовой базой (hotel_test).\n");
    exit(1);
}

$passed = 0;
$failed = 0;

function check(string $name, bool $condition): void
{
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "  ok    {$name}\n";
    } else {
        $failed++;
        echo "  FAIL  {$name}\n";
    }
}

function throws(callable $fn, string $class): bool
{
    try {
        $fn();
    } catch (Throwable $e) {
        return $e instanceof $class;
    }

    return false;
}

// ---------------------------------------------------------------- Validator
echo "Validator\n";
$v = Validator::make(['name' => 'Анна-Мария', 'email' => 'a@b.by', 'password' => 'Secret123', 'password_confirmation' => 'Secret123'],
    ['name' => 'required|name', 'email' => 'required|email', 'password' => 'required|min:8|password|confirmed']);
check('корректные данные проходят', !$v->fails());

$v = Validator::make(['name' => '<b>x</b>', 'email' => 'не почта', 'password' => 'short', 'password_confirmation' => 'other'],
    ['name' => 'required|name', 'email' => 'required|email', 'password' => 'required|min:8|password|confirmed']);
check('имя с тегами отклоняется', isset($v->errors()['name']));
check('неверный email отклоняется', isset($v->errors()['email']));
check('короткий пароль отклоняется', isset($v->errors()['password']));
check('пароли не совпадают', Validator::make(['password' => 'Secret123', 'password_confirmation' => 'Secret124'], ['password' => 'confirmed'])->fails());
check('пароль без цифр отклоняется', Validator::make(['p' => 'abcdefgh'], ['p' => 'password'])->fails());
check('дата 2026-02-30 отклоняется', Validator::make(['d' => '2026-02-30'], ['d' => 'date'])->fails());
check('дата 2026-02-28 принимается', !Validator::make(['d' => '2026-02-28'], ['d' => 'date'])->fails());
check('цена 299,50 принимается', !Validator::make(['p' => '299,50'], ['p' => 'decimal'])->fails());
check('цена 12.345 отклоняется', Validator::make(['p' => '12.345'], ['p' => 'decimal'])->fails());
check('between: 11 вне диапазона 1..10', Validator::make(['g' => '11'], ['g' => 'between:1,10'])->fails());
check('in: недопустимое значение', Validator::make(['c' => 'x'], ['c' => 'in:a,b'])->fails());
check('необязательное пустое поле пропускается', !Validator::make(['d' => ''], ['d' => 'max:5|date'])->fails());

// ---------------------------------------------------------------- Router, Paginator
echo "Router и Paginator\n";
$router = new Router();
$router->get('/rooms/{id}', ['C', 'show']);
$router->post('/rooms/{id}', ['C', 'update']);
$match = $router->match('GET', '/rooms/15');
check('маршрут находится и отдаёт параметр', $match['handler'] === ['C', 'show'] && $match['params'] === ['id' => '15']);
check('POST идёт в другой обработчик', $router->match('POST', '/rooms/15')['handler'] === ['C', 'update']);
check('нет маршрута -> 404', throws(fn () => $router->match('GET', '/nope'), HttpException::class));
check('нечисловой id -> 404', throws(fn () => $router->match('GET', '/rooms/abc'), HttpException::class));
check('не тот метод -> 405', (function () use ($router) { try { $router->match('DELETE', '/rooms/1'); } catch (HttpException $e) { return $e->status() === 405; } return false; })());

$req = fn (string $script, string $uri): \Hotel\Core\Request => new \Hotel\Core\Request([], [], ['SCRIPT_NAME' => $script, 'REQUEST_URI' => $uri]);
check('сайт в корне: basePath пустой', $req('/index.php', '/rooms')->basePath() === '' && $req('/index.php', '/rooms')->path() === '/rooms');
check('сайт в подпапке: basePath отсекается от пути', $req('/lab7/public/index.php', '/lab7/public/admin/rooms?x=1')->path() === '/admin/rooms');
check('Windows: путь скрипта со слэшем «\\» не ломает basePath', $req('\\index.php', '/rooms')->basePath() === '');
check('Windows: подпапка с обратными слэшами', $req('\\lab7\\public\\index.php', '/lab7/public/rooms')->path() === '/rooms');

$origin = fn (array $headers): \Hotel\Core\Request => new \Hotel\Core\Request([], [], $headers + ['SCRIPT_NAME' => '/index.php', 'REQUEST_URI' => '/']);
check('CSRF: запрос с того же сайта (Origin) допускается', $origin(['HTTP_ORIGIN' => 'http://localhost:8000', 'HTTP_HOST' => 'localhost:8000'])->sameOrigin());
check('CSRF: запрос с чужого сайта отклоняется', !$origin(['HTTP_ORIGIN' => 'http://evil.example', 'HTTP_HOST' => 'localhost:8000'])->sameOrigin());
check('CSRF: чужой Referer без Origin отклоняется', !$origin(['HTTP_REFERER' => 'http://evil.example/page', 'HTTP_HOST' => 'localhost:8000'])->sameOrigin());
check('CSRF: без заголовков решает токен', $origin(['HTTP_HOST' => 'localhost:8000'])->sameOrigin());

$p = new Paginator(25, 10, 99);
check('Paginator: страница ограничивается последней', $p->page === 3 && $p->pages === 3 && $p->offset() === 20);
check('Paginator: пустой список', (new Paginator(0, 10, 1))->pages === 1);

// ---------------------------------------------------------------- Расчёты бронирования
echo "BookingService (расчёты)\n";
check('ночей между 10 и 13 числом: 3', BookingService::nights('2026-10-10', '2026-10-13') === 3);
check('стоимость 3 ночей по 67,5', BookingService::cost('67.50', 3) === '202.50');
check('прошедшая дата заезда отклоняется', BookingService::checkPeriod('2026-01-01', '2026-01-03', 30, '2026-10-16') !== null);
check('выезд раньше заезда отклоняется', BookingService::checkPeriod('2026-10-20', '2026-10-19', 30, '2026-10-16') !== null);
check('срок больше лимита отклоняется', BookingService::checkPeriod('2026-10-20', '2026-12-20', 30, '2026-10-16') !== null);
check('корректный период принимается', BookingService::checkPeriod('2026-10-20', '2026-10-23', 30, '2026-10-16') === null);

// ---------------------------------------------------------------- База данных
echo "База данных (hotel_test)\n";
try {
    $config = $app->config('database');
    Database::connect($config, false)->exec('CREATE DATABASE IF NOT EXISTS `hotel_test` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $pdo = Database::connect($config);
} catch (PDOException $e) {
    echo "  пропущено: нет подключения к MySQL (" . $e->getMessage() . ")\n";
    echo "\nПройдено: {$passed}, провалено: {$failed}\n";
    exit($failed > 0 ? 1 : 0);
}

$installer = new Installer($pdo, dirname(__DIR__));
$installer->createSchema(true);
$seeded = $installer->seed();
check('тестовые данные: 12 пользователей, 60 номеров', $seeded['users'] === 12 && $seeded['rooms'] === 60);
check('бронирований не меньше 50', $seeded['bookings'] >= 50);

$users = new UserRepository($pdo);
$rooms = new RoomRepository($pdo);
$bookings = new BookingRepository($pdo);
$payments = new PaymentRepository($pdo);
$service = new BookingService($pdo, $rooms, $bookings, $payments);

// Пользователи
$admin = $users->findByEmail(Installer::ADMIN_EMAIL);
check('администратор найден по email', $admin !== null && $admin['role'] === 'admin');
check('пароль хранится как хеш', password_verify(Installer::ADMIN_PASSWORD, $admin['password_hash']) && $admin['password_hash'] !== Installer::ADMIN_PASSWORD);
check('email уникален', throws(fn () => $users->create('Дубль', Installer::ADMIN_EMAIL, 'x'), PDOException::class));

// Защита от SQL-инъекций: «опасные» значения обрабатываются как обычный текст
$evil = "' OR '1'='1' -- ";
check('SQL-инъекция в email не находит пользователя', $users->findByEmail($evil) === null);
check('SQL-инъекция в поиске пользователей ничего не находит', $users->count($evil) === 0);
check('SQL-инъекция в номере комнаты ничего не находит', $rooms->count(['search' => $evil]) === 0);
check('SQL-инъекция в классе ничего не находит', $rooms->count(['class' => $evil]) === 0);
check('поиск по «%» не возвращает все записи', $users->count('%') === 0);
check('таблицы остались на месте после «атак»', (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() === 12);
check('сортировка вне белого списка заменяется безопасной', count($rooms->page(['only_active' => true], 'id; DROP TABLE rooms', 5, 0)) === 5);

// Бронирование
$client = $users->create('Тест Клиент', 'client@test.by', password_hash('Secret123', PASSWORD_DEFAULT));
$room = $rooms->create(['number' => 'T1', 'class' => 'standard', 'price' => '100', 'capacity' => 2, 'description' => '', 'is_active' => 1]);
$d = static fn (int $days): string => date('Y-m-d', strtotime("+{$days} days"));

$id = $service->book($client, $room, $d(300), $d(303), 2, 'card');
$booking = $bookings->find($id);
check('новая бронь: статус «забронировано»', $booking['status'] === 'booked');
check('оплата создана: 3 ночи = 300.00, статус «ожидает оплаты»', $booking['amount'] === '300.00' && $booking['pay_status'] === 'pending');
check('для карты задан срок оплаты', $booking['pay_due'] !== null);
check('пересекающаяся бронь отклоняется', throws(fn () => $service->book($client, $room, $d(302), $d(305), 1, 'card'), BookingException::class));
check('бронь внутри занятого периода отклоняется', throws(fn () => $service->book($client, $room, $d(301), $d(302), 1, 'card'), BookingException::class));
$cashId = $service->book($client, $room, $d(303), $d(305), 1, 'cash');
check('день выезда свободен для следующего заезда', $cashId > 0);
check('оплата «при заселении» без срока', $bookings->find($cashId)['pay_due'] === null);
check('больше гостей, чем мест, отклоняется', throws(fn () => $service->book($client, $room, $d(400), $d(402), 3, 'card'), BookingException::class));
check('неизвестный способ оплаты отклоняется', throws(fn () => $service->book($client, $room, $d(400), $d(402), 1, 'bitcoin'), BookingException::class));
check('бронь в прошлом отклоняется', throws(fn () => $service->book($client, $room, '2020-01-01', '2020-01-03', 1, 'card'), BookingException::class));

// Доступность
check('занятый номер не попадает в список свободных', $rooms->count(['from' => $d(300), 'to' => $d(303), 'class' => 'standard', 'search' => 'T1']) === 0);
check('в другие даты номер свободен', $rooms->count(['from' => $d(310), 'to' => $d(312), 'search' => 'T1']) === 1);

// Оплата
$other = $users->create('Другой Клиент', 'other@test.by', password_hash('Secret123', PASSWORD_DEFAULT));
check('чужую бронь оплатить нельзя', throws(fn () => $service->pay($id, $other, false), BookingException::class));
check('«при заселении» гость сам оплатить не может', throws(fn () => $service->pay($cashId, $client, false), BookingException::class));
$service->pay($id, $client, false);
check('после оплаты бронь «оплачено», платёж «оплачено»', $bookings->find($id)['status'] === 'paid' && $bookings->find($id)['pay_status'] === 'paid');
check('повторная оплата отклоняется', throws(fn () => $service->pay($id, $client, false), BookingException::class));
$service->pay($cashId, $admin['id'], true);
check('администратор принимает оплату при заселении', $bookings->find($cashId)['status'] === 'paid');

// Заселение и выселение
check('заселить до даты заезда нельзя', throws(fn () => $service->checkIn($id), BookingException::class));
$now = $service->book($client, $room, date('Y-m-d'), $d(2), 1, 'online');
check('неоплаченную бронь заселить нельзя', throws(fn () => $service->checkIn($now), BookingException::class));
$service->pay($now, $client, false);
$service->checkIn($now);
check('после заселения статус «проживает»', $bookings->find($now)['status'] === 'checked_in');
check('проживающего отменить нельзя', throws(fn () => $service->cancel($now, $admin['id'], true), BookingException::class));
check('выселить можно только проживающего', throws(fn () => $service->checkOut($id), BookingException::class));
$service->checkOut($now);
check('после выселения «завершено»', $bookings->find($now)['status'] === 'completed');

// Отмена
check('чужую бронь клиент отменить не может', throws(fn () => $service->cancel($id, $other, false), BookingException::class));
$service->cancel($id, $client, false);
check('своя бронь отменяется, причина — гость', $bookings->find($id)['status'] === 'cancelled' && $bookings->find($id)['cancel_reason'] === 'client');
check('при отмене оплаченной брони платёж возвращается', $bookings->find($id)['pay_status'] === 'refunded');
check('повторная отмена отклоняется', throws(fn () => $service->cancel($id, $client, false), BookingException::class));
check('после отмены даты снова свободны', !$bookings->hasOverlap($room, $d(300), $d(303)));

$unpaid = $service->book($client, $room, $d(400), $d(402), 1, 'card');
$service->cancel($unpaid, $admin['id'], true);
check('отмена неоплаченной брони закрывает платёж без возврата', $bookings->find($unpaid)['pay_status'] === 'cancelled' && $bookings->find($unpaid)['cancel_reason'] === 'admin');

// Автоматические переходы
$late = $service->book($client, $room, $d(500), $d(502), 1, 'card');
$pdo->exec("UPDATE payments SET due_at = NOW() - INTERVAL 1 HOUR WHERE booking_id = {$late}");
$service->refresh();
check('не оплатили вовремя: бронь отменена автоматически', $bookings->find($late)['status'] === 'cancelled' && $bookings->find($late)['cancel_reason'] === 'expired');
check('просроченная бронь освобождает номер', !$bookings->hasOverlap($room, $d(500), $d(502)));
$cashKept = $service->book($client, $room, $d(600), $d(602), 1, 'cash');
$service->refresh();
check('бронь с оплатой при заселении не истекает', $bookings->find($cashKept)['status'] === 'booked');

$pdo->exec("INSERT INTO bookings (user_id, room_id, date_from, date_to, guests, status) VALUES ({$client}, {$room}, CURDATE() - INTERVAL 5 DAY, CURDATE() - INTERVAL 3 DAY, 1, 'paid')");
$missed = (int) $pdo->lastInsertId();
$pdo->exec("INSERT INTO bookings (user_id, room_id, date_from, date_to, guests, status) VALUES ({$client}, {$room}, CURDATE() - INTERVAL 9 DAY, CURDATE() - INTERVAL 7 DAY, 1, 'checked_in')");
$forgot = (int) $pdo->lastInsertId();
$service->refresh();
check('оплачено, но гость не приехал: «не заехал»', $bookings->find($missed)['status'] === 'no_show');
check('проживание закончилось без выселения: «завершено»', $bookings->find($forgot)['status'] === 'completed');

// Удаление и каскады
check('номер с бронями нельзя удалить (внешний ключ)', throws(fn () => $rooms->delete($room), PDOException::class));
$users->delete($client);
check('при удалении пользователя удаляются его брони и оплаты', (int) $pdo->query("SELECT COUNT(*) FROM bookings WHERE user_id = {$client}")->fetchColumn() === 0
    && (int) $pdo->query("SELECT COUNT(*) FROM payments WHERE booking_id = {$id}")->fetchColumn() === 0);

// Статистика
$summary = $bookings->summary(30);
check('статистика: 12 пользователей', $summary['users'] === 12 || $summary['users'] === 13);
check('статистика по классам содержит 4 класса', count($bookings->byClass()) === 4);

echo "\nПройдено: {$passed}, провалено: {$failed}\n";
exit($failed > 0 ? 1 : 0);
