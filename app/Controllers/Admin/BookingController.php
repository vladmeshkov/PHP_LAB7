<?php
declare(strict_types=1);

namespace Hotel\Controllers\Admin;

use Hotel\Core\Controller;
use Hotel\Core\Paginator;
use Hotel\Core\Request;
use Hotel\Core\Response;
use Hotel\Services\BookingException;

/** Все бронирования отеля: фильтр по статусу, поиск, отмена. */
final class BookingController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->query('status');
        $status = in_array($status, ['confirmed', 'cancelled'], true) ? $status : '';
        $search = mb_substr($request->query('q'), 0, 60);

        $bookings = $this->app->bookings();
        $paginator = new Paginator($bookings->countAll($status, $search), (int) config('per_page.admin'), (int) $request->query('page', '1'));

        return $this->view('admin/bookings/index', [
            'bookings'  => $bookings->pageAll($status, $search, $paginator->perPage, $paginator->offset()),
            'paginator' => $paginator,
            'status'    => $status,
            'search'    => $search,
            'today'     => date('Y-m-d'),
        ]);
    }

    public function cancel(Request $request, int $id): Response
    {
        try {
            $this->app->bookingService()->cancel($id, $this->app->auth()->id(), true);
            $this->flash('success', "Бронирование №{$id} отменено");
        } catch (BookingException $e) {
            $this->flash('danger', $e->getMessage());
        }

        return $this->redirect('admin/bookings');
    }
}
