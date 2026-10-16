<?php
/** Подписи и цвета для классов номеров и статусов бронирования. Подключается из шаблонов через require_once. */

if (!function_exists('class_badge')) {
    function class_badge(string $class): string
    {
        return '<span class="tag tag--' . e($class) . '">' . e(\Hotel\Repositories\RoomRepository::CLASSES[$class] ?? $class) . '</span>';
    }

    /** Статус брони: «подтверждена» превращается в «завершена», когда дата выезда уже прошла. */
    function status_badge(array $booking, string $today): string
    {
        if ($booking['status'] === 'cancelled') {
            return '<span class="pill pill--muted">Отменена</span>';
        }
        if ($booking['date_to'] < $today) {
            return '<span class="pill pill--done">Завершена</span>';
        }
        if ($booking['date_from'] <= $today) {
            return '<span class="pill pill--now">Проживание</span>';
        }

        return '<span class="pill pill--ok">Подтверждена</span>';
    }
}
