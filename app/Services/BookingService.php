<?php
declare(strict_types=1);

namespace Hotel\Services;

use DateTimeImmutable;
use Hotel\Repositories\BookingRepository;
use Hotel\Repositories\PaymentRepository;
use Hotel\Repositories\RoomRepository;
use PDO;

/**
 * Правила бронирования. Контроллер только передаёт данные, а проверки и транзакции находятся здесь.
 */
final class BookingService
{
    public function __construct(
        private PDO $pdo,
        private RoomRepository $rooms,
        private BookingRepository $bookings,
        private PaymentRepository $payments
    ) {
    }

    /** Число ночей между датами заезда и выезда. */
    public static function nights(string $from, string $to): int
    {
        return (int) (new DateTimeImmutable($from))->diff(new DateTimeImmutable($to))->format('%r%a');
    }

    /** Стоимость проживания в формате decimal(10,2). */
    public static function cost(string|float $price, int $nights): string
    {
        return number_format((float) $price * $nights, 2, '.', '');
    }

    /**
     * Проверка периода: не в прошлом, выезд позже заезда, не длиннее лимита.
     *
     * @return string|null текст ошибки или null, если период допустим
     */
    public static function checkPeriod(string $from, string $to, int $maxNights, ?string $today = null): ?string
    {
        $today ??= date('Y-m-d');

        if ($from < $today) {
            return 'Дата заезда не может быть в прошлом';
        }
        $nights = self::nights($from, $to);
        if ($nights < 1) {
            return 'Дата выезда должна быть позже даты заезда';
        }
        if ($nights > $maxNights) {
            return "Максимальный срок проживания – {$maxNights} суток";
        }

        return null;
    }

    /** Обновляет статусы, зависящие от времени. Вызывается перед показом списков и перед бронированием. */
    public function refresh(): void
    {
        $this->bookings->expireUnpaid();
        $this->bookings->markNoShows();
        $this->bookings->completeFinished();
    }

    /**
     * Создаёт бронь (статус «забронировано») и оплату (статус «ожидает оплаты») в одной транзакции.
     * Для карты и онлайн-оплаты задаётся срок: не оплатили вовремя – бронь отменяется.
     * Строка номера блокируется (FOR UPDATE), поэтому два клиента не смогут одновременно занять одни и те же даты.
     *
     * @throws BookingException если бронирование невозможно
     */
    public function book(int $userId, int $roomId, string $from, string $to, int $guests, string $method): int
    {
        if (($error = self::checkPeriod($from, $to, (int) config('max_nights', 30))) !== null) {
            throw new BookingException($error);
        }
        if (!isset(PaymentRepository::METHODS[$method])) {
            throw new BookingException('Выберите способ оплаты');
        }

        $this->refresh();   // просроченные брони освобождают номер до проверки занятости

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('SELECT id, price, capacity, is_active FROM rooms WHERE id = :id FOR UPDATE');
            $stmt->execute(['id' => $roomId]);
            $room = $stmt->fetch();

            if (!$room || !(int) $room['is_active']) {
                throw new BookingException('Номер недоступен для бронирования');
            }
            if ($guests < 1 || $guests > (int) $room['capacity']) {
                throw new BookingException('Количество гостей превышает вместимость номера (' . $room['capacity'] . ')');
            }
            if ($this->bookings->hasOverlap($roomId, $from, $to)) {
                throw new BookingException('Номер уже занят на выбранные даты');
            }

            $due = $method === 'cash' ? null : date('Y-m-d H:i:s', time() + (int) config('payment_hold_hours', 24) * 3600);
            $id = $this->bookings->create($userId, $roomId, $from, $to, $guests);
            $this->payments->create($id, self::cost($room['price'], self::nights($from, $to)), $method, $due);
            $this->pdo->commit();

            return $id;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Оплата брони гостем (онлайн-платёж условный). Бронь переходит в «оплачено».
     * Оплата «при заселении» принимается только администратором.
     *
     * @throws BookingException
     */
    public function pay(int $bookingId, int $actorId, bool $isAdmin): void
    {
        $booking = $this->owned($bookingId, $actorId, $isAdmin);

        if ($booking['status'] !== BookingStatus::BOOKED || $booking['pay_status'] !== 'pending') {
            throw new BookingException('Эта бронь не ожидает оплаты');
        }
        if (!$isAdmin && $booking['pay_method'] === 'cash') {
            throw new BookingException('Эта бронь оплачивается при заселении');
        }

        $this->transition($bookingId, BookingStatus::PAID, null, fn () => $this->payments->markPaid($bookingId));
    }

    /**
     * Отмена. Гость отменяет свою бронь до дня заезда, администратор – любую бронь до заселения.
     * Оплаченное возвращается, неоплаченное закрывается.
     *
     * @throws BookingException
     */
    public function cancel(int $bookingId, int $actorId, bool $isAdmin, ?string $today = null): void
    {
        $today ??= date('Y-m-d');
        $booking = $this->owned($bookingId, $actorId, $isAdmin);

        if (!in_array($booking['status'], BookingStatus::CANCELLABLE, true)) {
            throw new BookingException($booking['status'] === BookingStatus::CANCELLED
                ? 'Бронирование уже отменено'
                : 'Бронирование в статусе «' . BookingStatus::LABELS[$booking['status']] . '» отменить нельзя');
        }
        if (!$isAdmin && $booking['date_from'] <= $today) {
            throw new BookingException('Отменить можно только бронь, заезд по которой ещё не начался');
        }

        $this->transition($bookingId, BookingStatus::CANCELLED, $isAdmin ? 'admin' : 'client', fn () => $this->payments->close($bookingId));
    }

    /**
     * Заселение (администратор). Только оплаченная бронь и не раньше даты заезда.
     * Если гость платит при заселении, сначала принимается оплата.
     *
     * @throws BookingException
     */
    public function checkIn(int $bookingId, ?string $today = null): void
    {
        $today ??= date('Y-m-d');
        $booking = $this->bookings->find($bookingId) ?? throw new BookingException('Бронирование не найдено');

        if ($booking['status'] === BookingStatus::BOOKED) {
            throw new BookingException('Сначала нужно принять оплату: бронь ещё не оплачена');
        }
        if ($booking['status'] !== BookingStatus::PAID) {
            throw new BookingException('Заселить можно только оплаченную бронь');
        }
        if ($booking['date_from'] > $today) {
            throw new BookingException('Заезд по этой брони ещё не наступил (' . date_ru($booking['date_from']) . ')');
        }

        $this->transition($bookingId, BookingStatus::CHECKED_IN);
    }

    /** Выселение (администратор): проживание завершено. */
    public function checkOut(int $bookingId): void
    {
        $booking = $this->bookings->find($bookingId) ?? throw new BookingException('Бронирование не найдено');
        if ($booking['status'] !== BookingStatus::CHECKED_IN) {
            throw new BookingException('Выселить можно только проживающего гостя');
        }

        $this->transition($bookingId, BookingStatus::COMPLETED);
    }

    /** Бронь, к которой у пользователя есть доступ: своя, а для администратора любая. */
    private function owned(int $bookingId, int $actorId, bool $isAdmin): array
    {
        $booking = $this->bookings->find($bookingId);
        if ($booking === null || (!$isAdmin && (int) $booking['user_id'] !== $actorId)) {
            throw new BookingException('Бронирование не найдено');
        }

        return $booking;
    }

    /** Смена статуса вместе с сопутствующим действием в одной транзакции. */
    private function transition(int $bookingId, string $status, ?string $reason = null, ?callable $also = null): void
    {
        $this->pdo->beginTransaction();
        try {
            $this->bookings->setStatus($bookingId, $status, $reason);
            if ($also !== null) {
                $also();
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
