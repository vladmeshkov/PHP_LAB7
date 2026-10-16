<?php
declare(strict_types=1);

/**
 * Небольшие функции-помощники, доступные во всех файлах и шаблонах.
 */

use Hotel\Core\Application;

if (!function_exists('env')) {
    /** Значение переменной окружения (из .env или сервера). */
    function env(string $key, ?string $default = null): ?string
    {
        $value = $_ENV[$key] ?? getenv($key);

        return $value === false || $value === null ? $default : (string) $value;
    }
}

/** Экранирование вывода: защита от XSS. Все данные в шаблонах выводятся только через e(). */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Настройка из config/app.php по «точечному» ключу, например config('per_page.rooms'). */
function config(string $key, mixed $default = null): mixed
{
    return Application::instance()->config($key, $default);
}

/** Абсолютный путь внутри сайта с учётом папки, в которой лежит public/. */
function url(string $path = ''): string
{
    return Application::instance()->request()->basePath() . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

/** Скрытое поле с CSRF-токеном для каждой формы, изменяющей данные. */
function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(Application::instance()->csrf()->token()) . '">';
}

/** Старое значение поля формы после неудачной отправки. */
function old(string $key, mixed $default = ''): string
{
    return e(Application::instance()->session()->old($key, $default));
}

/** Деньги в белорусских рублях: 1 234,50 BYN. */
function money(float|string $amount): string
{
    return number_format((float) $amount, 2, ',', ' ') . ' ' . config('currency', 'BYN');
}

/** Дата из формата БД в привычный вид: 2026-10-16 -> 16.10.2026. */
function date_ru(string $date): string
{
    return date('d.m.Y', strtotime($date));
}
