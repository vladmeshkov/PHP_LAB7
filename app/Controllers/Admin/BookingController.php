<?php
declare(strict_types=1);

namespace Hotel\Controllers\Admin;

use Hotel\Core\Controller;
use Hotel\Core\Paginator;
use Hotel\Core\Request;
use Hotel\Core\Response;
use Hotel\Services\BookingException;
use Hotel\Services\BookingStatus;

/** Все бронирования отеля: фильтр по статусу, поиск, отмена. */
final class BookingController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->query('status');
        $status = isset(BookingStatus::LABELS[$status]) ? $status : '';
        $search = mb_substr($request->query('q'), 0, 60);

        $this->app->bookingService()->refresh();
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

    /** Принять оплату (например, при заселении, если гость платит на месте). */
    public function pay(Request $request, int $id): Response
    {
        return $this->act($id, 'Оплата по бронированию №%d принята', fn () => $this->app->bookingService()->pay($id, $this->app->auth()->id(), true));
    }

    public function checkIn(Request $request, int $id): Response
    {
        return $this->act($id, 'Гость по бронированию №%d заселён', fn () => $this->app->bookingService()->checkIn($id));
    }

    public function checkOut(Request $request, int $id): Response
    {
        return $this->act($id, 'Гость по бронированию №%d выселен', fn () => $this->app->bookingService()->checkOut($id));
    }

    public function cancel(Request $request, int $id): Response
    {
        return $this->act($id, 'Бронирование №%d отменено', fn () => $this->app->bookingService()->cancel($id, $this->app->auth()->id(), true));
    }

    /** Выполняет действие над бронью и возвращает на список с сообщением. */
    private function act(int $id, string $success, callable $action): Response
    {
        try {
            $action();
            $this->flash('success', sprintf($success, $id));
        } catch (BookingException $e) {
            $this->flash('danger', $e->getMessage());
        }

        return $this->redirect('admin/bookings');
    }
}
