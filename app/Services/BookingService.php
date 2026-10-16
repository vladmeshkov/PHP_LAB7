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

    /**
     * Создаёт бронь и оплату в одной транзакции.
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

            $id = $this->bookings->create($userId, $roomId, $from, $to, $guests);
            $this->payments->create($id, self::cost($room['price'], self::nights($from, $to)), $method);
            $this->pdo->commit();

            return $id;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Отмена брони. Клиент может отменить только свою бронь до дня заезда, администратор – любую неоконченную.
     *
     * @throws BookingException
     */
    public function cancel(int $bookingId, int $actorId, bool $isAdmin, ?string $today = null): void
    {
        $today ??= date('Y-m-d');
        $booking = $this->bookings->find($bookingId);

        if ($booking === null || (!$isAdmin && (int) $booking['user_id'] !== $actorId)) {
            throw new BookingException('Бронирование не найдено');
        }
        if ($booking['status'] !== 'confirmed') {
            throw new BookingException('Бронирование уже отменено');
        }
        if (!$isAdmin && $booking['date_from'] <= $today) {
            throw new BookingException('Отменить можно только бронь, заезд по которой ещё не начался');
        }
        if ($isAdmin && $booking['date_to'] < $today) {
            throw new BookingException('Проживание уже завершено');
        }

        $this->pdo->beginTransaction();
        try {
            $this->bookings->cancel($bookingId);
            $this->payments->refund($bookingId);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
