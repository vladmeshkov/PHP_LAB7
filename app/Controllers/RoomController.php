<?php
declare(strict_types=1);

namespace Hotel\Controllers;

use Hotel\Core\Controller;
use Hotel\Core\HttpException;
use Hotel\Core\Paginator;
use Hotel\Core\Request;
use Hotel\Core\Response;
use Hotel\Repositories\PaymentRepository;
use Hotel\Repositories\RoomRepository;
use Hotel\Services\BookingService;

/** Каталог номеров: просмотр доступных номеров, фильтр по классу и датам. */
final class RoomController extends Controller
{
    /** Главная: поиск по датам и обзор классов номеров. */
    public function home(): Response
    {
        return $this->view('rooms/home', [
            'classes' => RoomRepository::CLASSES,
            'summary' => $this->app->rooms()->summaryByClass(),
        ]);
    }

    public function index(Request $request): Response
    {
        $from = $request->query('from');
        $to = $request->query('to');
        $class = $request->query('class');
        $guests = $request->query('guests');
        $sort = $request->query('sort', 'number');

        $filters = ['only_active' => true];
        $notice = null;

        if (isset(RoomRepository::CLASSES[$class])) {
            $filters['class'] = $class;
        } else {
            $class = '';
        }
        if (ctype_digit($guests) && (int) $guests >= 1 && (int) $guests <= 10) {
            $filters['guests'] = (int) $guests;
        } else {
            $guests = '';
        }

        // Фильтр по датам применяется только если период корректен; иначе показываем причину.
        if ($from !== '' || $to !== '') {
            $v = $this->period($from, $to);
            if ($v === null) {
                $filters += ['from' => $from, 'to' => $to];
            } else {
                $notice = $v;
            }
        }

        $paginator = new Paginator($this->app->rooms()->count($filters), (int) config('per_page.rooms'), (int) $request->query('page', '1'));
        $rooms = $this->app->rooms()->page($filters, $sort, $paginator->perPage, $paginator->offset());

        return $this->view('rooms/index', [
            'rooms'     => $rooms,
            'paginator' => $paginator,
            'filters'   => compact('from', 'to', 'class', 'guests', 'sort'),
            'notice'    => $notice,
            'dated'     => isset($filters['from']),
            'nights'    => isset($filters['from']) ? BookingService::nights($from, $to) : 0,
            'classes'   => RoomRepository::CLASSES,
        ]);
    }

    public function show(Request $request, int $id): Response
    {
        $room = $this->app->rooms()->find($id);
        if ($room === null || !(int) $room['is_active']) {
            throw new HttpException(404);
        }

        $from = $request->query('from') ?: date('Y-m-d', strtotime('+1 day'));
        $to = $request->query('to') ?: date('Y-m-d', strtotime('+3 days'));

        return $this->view('rooms/show', [
            'room'      => $room,
            'classes'   => RoomRepository::CLASSES,
            'methods'   => PaymentRepository::METHODS,
            'busy'      => $this->app->bookings()->upcomingForRoom($id),
            'from'      => $from,
            'to'        => $to,
            'guests'    => (int) $request->query('guests', '1') ?: 1,
        ]);
    }

    /** Проверка периода из строки запроса. */
    private function period(string $from, string $to): ?string
    {
        if ($from === '' || $to === '') {
            return 'Укажите обе даты: заезд и выезд';
        }
        $isDate = static fn (string $d): bool => ($x = \DateTimeImmutable::createFromFormat('!Y-m-d', $d)) !== false && $x->format('Y-m-d') === $d;
        if (!$isDate($from) || !$isDate($to)) {
            return 'Даты указаны в неверном формате';
        }

        return BookingService::checkPeriod($from, $to, (int) config('max_nights', 30));
    }
}
