<?php
declare(strict_types=1);

/**
 * Общие настройки приложения. Значения окружения берутся из .env (см. .env.example).
 */
return [
    'name'       => 'Отель «Центральный»',
    'debug'      => filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN),
    'timezone'   => 'Europe/Minsk',
    'currency'   => 'BYN',

    // Сколько записей показывать на странице
    'per_page'   => ['rooms' => 9, 'admin' => 12, 'history' => 8],

    // Ограничения бронирования
    'max_nights' => 30,

    // Защита входа: после стольких неудачных попыток вход блокируется на указанное число секунд
    'login_attempts' => 5,
    'login_lockout'  => 300,
];
