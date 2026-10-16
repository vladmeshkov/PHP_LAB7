<?php
declare(strict_types=1);

namespace Hotel\Core;

use PDO;

/** Создаёт подключение PDO с безопасными настройками. */
final class Database
{
    public static function connect(array $c, bool $withDatabase = true): PDO
    {
        $dsn = sprintf('mysql:host=%s;port=%d;%scharset=%s', $c['host'], $c['port'], $withDatabase ? 'dbname=' . $c['name'] . ';' : '', $c['charset']);

        return new PDO($dsn, $c['user'], $c['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // ошибки SQL – исключения
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,                    // настоящие подготовленные запросы: данные не смешиваются с SQL
        ]);
    }
}
