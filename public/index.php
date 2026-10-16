<?php
declare(strict_types=1);

// Встроенный сервер PHP (php -S ... public/index.php): существующие файлы (css, js) отдаём как есть.
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($file) && realpath($file) !== __FILE__) {
        return false;
    }
}

// Единственная точка входа: все адреса сайта обрабатываются здесь (см. config/routes.php).
$app = require dirname(__DIR__) . '/app/bootstrap.php';
$app->run();
