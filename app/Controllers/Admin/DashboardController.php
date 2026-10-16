<?php
declare(strict_types=1);

namespace Hotel\Controllers\Admin;

use Hotel\Core\Controller;
use Hotel\Core\Response;
use Hotel\Repositories\RoomRepository;

/** Админ-панель: общая статистика. */
final class DashboardController extends Controller
{
    public function index(): Response
    {
        $bookings = $this->app->bookings();

        return $this->view('admin/dashboard', [
            'summary'  => $bookings->summary(30),
            'byClass'  => $bookings->byClass(),
            'byStatus' => $bookings->byStatus(),
            'latest'   => $bookings->latest(6),
            'classes'  => RoomRepository::CLASSES,
        ]);
    }
}
