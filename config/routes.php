<?php
declare(strict_types=1);

use Hotel\Controllers\Admin\BookingController as AdminBookings;
use Hotel\Controllers\Admin\DashboardController;
use Hotel\Controllers\Admin\RoomController as AdminRooms;
use Hotel\Controllers\Admin\UserController as AdminUsers;
use Hotel\Controllers\AuthController;
use Hotel\Controllers\BookingController;
use Hotel\Controllers\RoomController;
use Hotel\Core\Router;

/**
 * Таблица маршрутов: метод + адрес => [контроллер, метод] и правила доступа.
 * Правила: guest (только для гостей), auth (нужен вход), admin (только администратор).
 */
return static function (Router $r): void {
    // Каталог номеров (доступен всем)
    $r->get('/', [RoomController::class, 'home']);
    $r->get('/rooms', [RoomController::class, 'index']);
    $r->get('/rooms/{id}', [RoomController::class, 'show']);

    // Регистрация, вход, выход
    $r->get('/register', [AuthController::class, 'registerForm'], ['guest']);
    $r->post('/register', [AuthController::class, 'register'], ['guest']);
    $r->get('/login', [AuthController::class, 'loginForm'], ['guest']);
    $r->post('/login', [AuthController::class, 'login'], ['guest']);
    $r->post('/logout', [AuthController::class, 'logout'], ['auth']);

    // Бронирования клиента
    $r->get('/bookings', [BookingController::class, 'index'], ['auth']);
    $r->post('/bookings', [BookingController::class, 'store'], ['auth']);
    $r->post('/bookings/{id}/pay', [BookingController::class, 'pay'], ['auth']);
    $r->post('/bookings/{id}/cancel', [BookingController::class, 'cancel'], ['auth']);

    // Админ-панель
    $r->get('/admin', [DashboardController::class, 'index'], ['admin']);

    $r->get('/admin/rooms', [AdminRooms::class, 'index'], ['admin']);
    $r->get('/admin/rooms/create', [AdminRooms::class, 'create'], ['admin']);
    $r->post('/admin/rooms', [AdminRooms::class, 'store'], ['admin']);
    $r->get('/admin/rooms/{id}/edit', [AdminRooms::class, 'edit'], ['admin']);
    $r->post('/admin/rooms/{id}', [AdminRooms::class, 'update'], ['admin']);
    $r->post('/admin/rooms/{id}/delete', [AdminRooms::class, 'destroy'], ['admin']);

    $r->get('/admin/users', [AdminUsers::class, 'index'], ['admin']);
    $r->post('/admin/users/{id}/role', [AdminUsers::class, 'role'], ['admin']);
    $r->post('/admin/users/{id}/delete', [AdminUsers::class, 'destroy'], ['admin']);

    $r->get('/admin/bookings', [AdminBookings::class, 'index'], ['admin']);
    $r->post('/admin/bookings/{id}/pay', [AdminBookings::class, 'pay'], ['admin']);
    $r->post('/admin/bookings/{id}/check-in', [AdminBookings::class, 'checkIn'], ['admin']);
    $r->post('/admin/bookings/{id}/check-out', [AdminBookings::class, 'checkOut'], ['admin']);
    $r->post('/admin/bookings/{id}/cancel', [AdminBookings::class, 'cancel'], ['admin']);
};
