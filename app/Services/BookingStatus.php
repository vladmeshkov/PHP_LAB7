<?php
declare(strict_types=1);

namespace Hotel\Services;

/**
 * Жизненный цикл бронирования.
 *
 *   забронировано ──оплата──▶ оплачено ──заселение──▶ проживает ──выселение──▶ завершено
 *        │                       │
 *        │                       └──не приехал──▶ не заехал (оплата остаётся)
 *        └──отмена / не оплатили вовремя──▶ отменено (оплаченное возвращается)
 */
final class BookingStatus
{
    public const BOOKED = 'booked';
    public const PAID = 'paid';
    public const CHECKED_IN = 'checked_in';
    public const COMPLETED = 'completed';
    public const NO_SHOW = 'no_show';
    public const CANCELLED = 'cancelled';

    public const LABELS = [
        self::BOOKED     => 'Забронировано',
        self::PAID       => 'Оплачено',
        self::CHECKED_IN => 'Проживает',
        self::COMPLETED  => 'Завершено',
        self::NO_SHOW    => 'Не заехал',
        self::CANCELLED  => 'Отменено',
    ];

    /** Статусы, при которых номер занят на даты брони. */
    public const BLOCKING = [self::BOOKED, self::PAID, self::CHECKED_IN];

    /** Что можно отменить: до заселения. */
    public const CANCELLABLE = [self::BOOKED, self::PAID];

    public const PAYMENT_LABELS = [
        'pending'   => 'Ожидает оплаты',
        'paid'      => 'Оплачено',
        'refunded'  => 'Возвращено',
        'cancelled' => 'Не оплачивалось',
    ];

    public const CANCEL_REASONS = ['client' => 'отменил гость', 'admin' => 'отменил администратор', 'expired' => 'не оплачено вовремя'];

    /** Список для SQL: 'booked','paid','checked_in'. */
    public static function blockingSql(): string
    {
        return "'" . implode("','", self::BLOCKING) . "'";
    }
}
