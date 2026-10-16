<?php
/**
 * Помощники для шаблонов: метки, иконки, картинки и удобства номеров.
 * Подключается из шаблонов через require_once.
 */

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

    /** Иконка из встроенного набора (линейные, 24×24). */
    function icon(string $name): string
    {
        static $paths = [
            'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
            'users'    => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
            'bed'      => '<path d="M2 4v16M2 8h18a2 2 0 0 1 2 2v10M2 17h20M6 8v9"/>',
            'wallet'   => '<path d="M20 12V8H6a2 2 0 0 1 0-4h12v4"/><path d="M4 6v12a2 2 0 0 0 2 2h14v-4"/><path d="M18 12a2 2 0 0 0 0 4h4v-4z"/>',
            'chart'    => '<path d="M3 3v18h18"/><path d="M18 9l-5 5-4-4-3 3"/>',
            'wifi'     => '<path d="M5 12.55a11 11 0 0 1 14.08 0M1.42 9a16 16 0 0 1 21.16 0M8.53 16.11a6 6 0 0 1 6.95 0"/><circle cx="12" cy="20" r="1"/>',
            'coffee'   => '<path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4z"/><path d="M6 1v3M10 1v3M14 1v3"/>',
            'car'      => '<path d="M5 17h14M3 13l2-6a2 2 0 0 1 2-1h10a2 2 0 0 1 2 1l2 6v4H3z"/><circle cx="7.5" cy="17" r="1.5"/><circle cx="16.5" cy="17" r="1.5"/>',
            'shield'   => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
            'check'    => '<path d="M20 6L9 17l-5-5"/>',
            'map'      => '<path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
            'phone'    => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/>',
            'key'      => '<circle cx="8" cy="15" r="4"/><path d="M10.85 12.15L19 4M18 5l3 3M15 8l3 3"/>',
        ];

        return '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true">' . ($paths[$name] ?? '') . '</svg>';
    }

    /** Иллюстрация номера по его классу. */
    function room_image(string $class): string
    {
        return asset('img/rooms/' . (isset(\Hotel\Repositories\RoomRepository::CLASSES[$class]) ? $class : 'standard') . '.svg');
    }

    /** Что входит в номер: каждый следующий класс включает удобства предыдущего. */
    function room_amenities(string $class): array
    {
        $levels = [
            'economy'  => ['Бесплатный Wi-Fi', 'Душевая кабина', 'Телевизор', 'Рабочий стол'],
            'standard' => ['Кондиционер', 'Чайник и чай', 'Сейф', 'Двуспальная кровать'],
            'comfort'  => ['Мини-бар', 'Вид на город', 'Халаты и тапочки', 'Зона отдыха'],
            'suite'    => ['Гостиная и кабинет', 'Ванна с джакузи', 'Завтрак включён', 'Парковка бесплатно'],
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

    /** Две первые буквы имени для аватара. */
    function initials(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name)) ?: [];

        return mb_strtoupper(mb_substr($parts[0] ?? '', 0, 1) . mb_substr($parts[1] ?? '', 0, 1));
    }
}
