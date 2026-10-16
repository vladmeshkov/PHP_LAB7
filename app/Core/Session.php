<?php
declare(strict_types=1);

namespace Hotel\Core;

/** Работа с сессией: запуск с безопасными параметрами, одноразовые сообщения, «старые» значения форм. */
final class Session
{
    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_name('hotel_session');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,                 // недоступна из JavaScript
            'samesite' => 'Lax',                // не отправляется со сторонних сайтов при POST
            'secure'   => !empty($_SERVER['HTTPS']),
        ]);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_start();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /** Новый идентификатор сессии: вызывается после входа, чтобы нельзя было подсунуть чужой (session fixation). */
    public function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $p['path'], 'httponly' => true, 'samesite' => 'Lax']);
        }
        session_destroy();
    }

    /** Одноразовое сообщение (показывается на следующей странице и исчезает). */
    public function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }

    public function pullFlash(): array
    {
        $messages = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);

        return $messages;
    }

    /** Сохраняет введённые значения, чтобы вернуть их в форму после ошибки. */
    public function flashInput(array $input): void
    {
        unset($input['password'], $input['password_confirmation']);   // пароли в сессии не держим
        $_SESSION['_old'] = $input;
    }

    public function old(string $key, mixed $default = ''): mixed
    {
        return $_SESSION['_old'][$key] ?? $default;
    }

    public function errors(): array
    {
        return $_SESSION['_errors'] ?? [];
    }

    public function flashErrors(array $errors): void
    {
        $_SESSION['_errors'] = $errors;
    }

    /** Очистка одноразовых данных после показа страницы. */
    public function clearTransient(): void
    {
        unset($_SESSION['_old'], $_SESSION['_errors']);
    }
}
