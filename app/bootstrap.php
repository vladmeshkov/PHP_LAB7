<?php
declare(strict_types=1);

/**
 * Подготовка приложения: помощники, автозагрузка классов, переменные окружения.
 * Подключается из public/index.php, bin/install.php и tests/run.php.
 */

$root = dirname(__DIR__);

require $root . '/app/Support/helpers.php';

// Автозагрузка по стандарту PSR-4: Hotel\Core\Router -> app/Core/Router.php
spl_autoload_register(static function (string $class) use ($root): void {
    $prefix = 'Hotel\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $file = $root . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

// Файл .env: строки вида КЛЮЧ=значение (комментарии начинаются с #)
$envFile = $root . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $value = trim($value);
        // Значение можно взять в кавычки (нужно, если в пароле есть пробелы или #)
        if (strlen($value) >= 2 && in_array($value[0], ['"', "'"], true) && $value[-1] === $value[0]) {
            $value = substr($value, 1, -1);
        }
        // Значения, уже заданные снаружи (командная строка, сервер), важнее файла .env
        $_ENV[trim($key)] ??= $value;
    }
}

return Hotel\Core\Application::boot($root);
