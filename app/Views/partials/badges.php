<?php
/**
 * Помощники для шаблонов: метки класса номера, статусы бронирования и оплаты, удобства номера.
 * Подключается в View, поэтому доступен на всех страницах.
 */

use Hotel\Services\BookingStatus;

if (!function_exists('class_badge')) {
    function class_badge(string $class): string
    {
        return '<span class="tag tag--' . e($class) . '">' . e(\Hotel\Repositories\RoomRepository::CLASSES[$class] ?? $class) . '</span>';
    }

    /** Статус бронирования цветной меткой. */
    function status_badge(array $booking): string
    {
        $modifier = [
            BookingStatus::BOOKED     => 'warn',
            BookingStatus::PAID       => 'ok',
            BookingStatus::CHECKED_IN => 'now',
            BookingStatus::COMPLETED  => 'done',
            BookingStatus::NO_SHOW    => 'bad',
            BookingStatus::CANCELLED  => 'muted',
        ][$booking['status']] ?? 'done';

        return '<span class="pill pill--' . $modifier . '">' . e(BookingStatus::LABELS[$booking['status']] ?? $booking['status']) . '</span>';
    }

    /** Подпись к статусу: что с оплатой и что делать дальше. */
    function payment_note(array $booking): string
    {
        $method = \Hotel\Repositories\PaymentRepository::METHODS[$booking['pay_method']] ?? '';

        return match ($booking['pay_status']) {
            'pending'   => $booking['pay_due']
                ? 'Оплатить до ' . date('d.m H:i', strtotime($booking['pay_due']))
                : 'Оплата при заселении',
            'paid'      => $method . ', оплачено',
            'refunded'  => 'Оплата возвращена',
            'cancelled' => 'Не оплачивалось',
            default     => '',
        };
    }

    /** Причина отмены словами. */
    function cancel_note(array $booking): string
    {
        return $booking['status'] === BookingStatus::CANCELLED && $booking['cancel_reason']
            ? BookingStatus::CANCEL_REASONS[$booking['cancel_reason']] ?? ''
            : '';
    }

    /** Две первые буквы имени для аватара. */
    function initials(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name)) ?: [];

        return mb_strtoupper(mb_substr($parts[0] ?? '', 0, 1) . mb_substr($parts[1] ?? '', 0, 1));
    }

    /** Что входит в номер: каждый следующий класс включает удобства предыдущего. */
    function room_amenities(string $class): array
    {
        $levels = [
            'economy'  => ['Wi-Fi', 'Душевая кабина', 'Телевизор', 'Рабочий стол'],
            'standard' => ['Кондиционер', 'Чайник', 'Сейф', 'Двуспальная кровать'],
            'comfort'  => ['Мини-бар', 'Вид на город', 'Халаты и тапочки', 'Зона отдыха'],
            'suite'    => ['Гостиная и кабинет', 'Ванна с джакузи', 'Завтрак включён', 'Парковка'],
        ];
        $result = [];
        foreach ($levels as $key => $items) {
            $result = array_merge($result, $items);
            if ($key === $class) {
                break;
            }
        }

        return $result;
    }
}
