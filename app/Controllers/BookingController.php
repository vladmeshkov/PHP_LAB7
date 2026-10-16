<?php
declare(strict_types=1);

namespace Hotel\Controllers;

use Hotel\Core\Controller;
use Hotel\Core\Paginator;
use Hotel\Core\Request;
use Hotel\Core\Response;
use Hotel\Core\Validator;
use Hotel\Repositories\PaymentRepository;
use Hotel\Services\BookingException;

/** Бронирования клиента: создание, история, отмена. */
final class BookingController extends Controller
{
    public function index(Request $request): Response
    {
        // У администратора нет собственных бронирований: все брони отеля – в админ-панели.
        if ($this->app->auth()->isAdmin()) {
            return $this->redirect('admin/bookings');
        }

        $userId = $this->app->auth()->id();
        $paginator = new Paginator($this->app->bookings()->countForUser($userId), (int) config('per_page.history'), (int) $request->query('page', '1'));

        return $this->view('bookings/index', [
            'bookings'  => $this->app->bookings()->pageForUser($userId, $paginator->perPage, $paginator->offset()),
            'paginator' => $paginator,
            'today'     => date('Y-m-d'),
        ]);
    }

    public function store(Request $request): Response
    {
        if ($this->app->auth()->isAdmin()) {
            $this->flash('warning', 'Администратор не оформляет бронирования от своего имени');

            return $this->redirect('admin/bookings');
        }

        $roomId = (int) $request->input('room_id');
        $back = 'rooms/' . $roomId;

        $v = Validator::make($request->all(), [
            'room_id'   => 'required|int',
            'date_from' => 'required|date',
            'date_to'   => 'required|date',
            'guests'    => 'required|between:1,10',
            'method'    => 'required|in:' . implode(',', array_keys(PaymentRepository::METHODS)),
        ], ['date_from' => 'Дата заезда', 'date_to' => 'Дата выезда', 'guests' => 'Гости', 'method' => 'Способ оплаты']);

        if ($v->fails()) {
            return $this->backWithErrors($back, $request, $v->errors());
        }

        try {
            $id = $this->app->bookingService()->book(
                $this->app->auth()->id(),
                $roomId,
                $request->input('date_from'),
                $request->input('date_to'),
                (int) $request->input('guests'),
                $request->input('method')
            );
        } catch (BookingException $e) {
            return $this->backWithErrors($back, $request, ['form' => $e->getMessage()]);
        }

        $this->flash('success', "Бронирование №{$id} подтверждено");

        return $this->redirect('bookings');
    }

    public function cancel(Request $request, int $id): Response
    {
        try {
            $this->app->bookingService()->cancel($id, $this->app->auth()->id(), false);
            $this->flash('success', "Бронирование №{$id} отменено, оплата возвращена");
        } catch (BookingException $e) {
            $this->flash('danger', $e->getMessage());
        }

        return $this->redirect('bookings');
    }
}
