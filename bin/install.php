<?php
declare(strict_types=1);

/**
 * Установка базы данных.
 *
 *   php bin/install.php                     создать БД и таблицы, добавить тестовые данные (если таблицы пусты)
 *   php bin/install.php --fresh             удалить таблицы и создать всё заново
 *   php bin/install.php --user=root --password=секрет [--host=127.0.0.1] [--db=hotel]
 *                                           указать параметры подключения прямо в команде
 *
 * Если файла .env нет или MySQL не принимает логин и пароль, скрипт спросит их сам и сохранит в .env.
 */

use Hotel\Core\Database;
use Hotel\Database\Installer;

if (PHP_SAPI !== 'cli') {
    exit('Скрипт запускается только из командной строки');
}

$root = dirname(__DIR__);

/** Параметры вида --name=value из командной строки. */
function option(string $name): ?string
{
    global $argv;
    foreach ($argv as $arg) {
        if (str_starts_with($arg, "--{$name}=")) {
            return substr($arg, strlen($name) + 3);
        }
    }

    return null;
}

/** Вопрос в консоли. Пароль вводится без отображения символов (где это возможно). */
function ask(string $question, string $default = '', bool $secret = false): string
{
    echo $question . ($default !== '' ? " [{$default}]" : '') . ': ';
    $hide = $secret && DIRECTORY_SEPARATOR === '/' && function_exists('shell_exec');
    if ($hide) {
        shell_exec('stty -echo 2>/dev/null');
    }
    $answer = trim((string) fgets(STDIN));
    if ($hide) {
        shell_exec('stty echo 2>/dev/null');
        echo "\n";
    }

    return $answer !== '' ? $answer : $default;
}

/** Записывает параметры подключения в .env (остальные строки сохраняются). */
function saveEnv(string $file, array $values): void
{
    $lines = is_file($file) ? file($file, FILE_IGNORE_NEW_LINES) : (file(dirname($file) . '/.env.example', FILE_IGNORE_NEW_LINES) ?: []);
    foreach ($values as $key => $value) {
        $quoted = preg_match('/[\s#"\']/', $value) ? "'" . $value . "'" : $value;
        $found = false;
        foreach ($lines as $i => $line) {
            if (str_starts_with($line, $key . '=')) {
                $lines[$i] = $key . '=' . $quoted;
                $found = true;
            }
        }
        if (!$found) {
            $lines[] = $key . '=' . $quoted;
        }
    }
    file_put_contents($file, implode("\n", $lines) . "\n");
}

// Параметры из командной строки важнее .env
foreach (['host' => 'DB_HOST', 'user' => 'DB_USER', 'password' => 'DB_PASS', 'db' => 'DB_NAME'] as $opt => $key) {
    if (($value = option($opt)) !== null) {
        $_ENV[$key] = $value;
    }
}
$fromCli = option('user') !== null || option('password') !== null;

$app = require $root . '/app/bootstrap.php';
$config = $app->config('database');
$interactive = function_exists('stream_isatty') && stream_isatty(STDIN);

// Нет .env — спрашиваем параметры сразу
if (!is_file($root . '/.env') && !$fromCli && $interactive) {
    echo "Файл .env не найден. Укажите параметры подключения к MySQL.\n";
    $config['user'] = ask('Логин MySQL', 'root');
    $config['password'] = ask('Пароль MySQL (может быть пустым)', '', true);
    $config['name'] = ask('Имя базы данных', (string) $config['name']);
    $asked = true;
}

$server = null;
for ($attempt = 1; $attempt <= 3; $attempt++) {
    if (!preg_match('/^\w+$/', (string) $config['name'])) {
        fwrite(STDERR, "Имя базы данных может содержать только буквы, цифры и _\n");
        exit(1);
    }

    try {
        // Сначала подключаемся без выбора базы, чтобы создать её при необходимости.
        $server = Database::connect($config, false);
        break;
    } catch (PDOException $e) {
        $denied = ($e->errorInfo[1] ?? 0) === 1045 || str_contains($e->getMessage(), 'Access denied');
        fwrite(STDERR, ($denied ? 'MySQL не принял логин или пароль' : 'Не удалось подключиться к MySQL: ' . $e->getMessage()) . "\n");

        if (!$denied || !$interactive || $fromCli || $attempt === 3) {
            fwrite(STDERR, "Проверьте значения DB_USER и DB_PASS в файле .env (или запустите: php bin/install.php --user=root --password=ваш_пароль)\n");
            exit(1);
        }
        $config['user'] = ask('Логин MySQL', (string) $config['user']);
        $config['password'] = ask('Пароль MySQL', '', true);
        $asked = true;
    }
}

try {
    $server->exec("CREATE DATABASE IF NOT EXISTS `{$config['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    $pdo = Database::connect($config);
    $installer = new Installer($pdo, $root);

    $installer->createSchema(in_array('--fresh', $argv, true));
    echo "Таблицы созданы.\n";

    if ($installer->isEmpty()) {
        $result = $installer->seed();
        echo "Добавлено: пользователей {$result['users']}, номеров {$result['rooms']}, бронирований {$result['bookings']}.\n";
        echo 'Администратор: ' . Installer::ADMIN_EMAIL . ' / ' . Installer::ADMIN_PASSWORD . "\n";
        echo 'Клиенты (пароль ' . Installer::CLIENT_PASSWORD . "): см. таблицу users\n";
    } else {
        echo "Данные уже есть, заполнение пропущено (используйте --fresh для полной переустановки).\n";
    }

    // Рабочие параметры сохраняем, чтобы сайт подключался к той же базе
    if (!empty($asked) || $fromCli || !is_file($root . '/.env')) {
        saveEnv($root . '/.env', ['DB_HOST' => (string) $config['host'], 'DB_NAME' => (string) $config['name'], 'DB_USER' => (string) $config['user'], 'DB_PASS' => (string) $config['password']]);
        echo "Параметры подключения сохранены в .env\n";
    }
} catch (PDOException $e) {
    fwrite(STDERR, 'Ошибка базы данных: ' . $e->getMessage() . "\n");
    exit(1);
}
