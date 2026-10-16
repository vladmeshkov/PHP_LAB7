<?php
declare(strict_types=1);

/**
 * Параметры подключения к MySQL / MariaDB. Меняются в файле .env, а не здесь.
 */
return [
    'host'     => env('DB_HOST', '127.0.0.1'),
    'port'     => (int) env('DB_PORT', '3306'),
    'name'     => env('DB_NAME', 'hotel'),
    'user'     => env('DB_USER', 'root'),
    'password' => env('DB_PASS', ''),
    'charset'  => 'utf8mb4',
];
