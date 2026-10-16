<?php
declare(strict_types=1);

namespace Hotel\Core;

use Hotel\Repositories\UserRepository;

/** Вход, выход и текущий пользователь. */
final class Auth
{
    private ?array $user = null;
    private bool $loaded = false;

    public function __construct(private Session $session, private Csrf $csrf, private UserRepository $users)
    {
    }

    /** Текущий пользователь. Данные берутся из БД при каждом запросе, поэтому смена роли действует сразу. */
    public function user(): ?array
    {
        if (!$this->loaded) {
            $this->loaded = true;
            $id = (int) $this->session->get('user_id', 0);
            $this->user = $id > 0 ? $this->users->find($id) : null;
            if ($id > 0 && $this->user === null) {
                $this->session->forget('user_id');   // пользователя удалили
            }
        }

        return $this->user;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function id(): int
    {
        return (int) ($this->user()['id'] ?? 0);
    }

    public function isAdmin(): bool
    {
        return ($this->user()['role'] ?? '') === 'admin';
    }

    /**
     * Проверка логина и пароля. Сообщение об ошибке одинаково для «нет такого email» и «неверный пароль»,
     * чтобы нельзя было перебором узнать, кто зарегистрирован.
     */
    public function attempt(string $email, string $password): bool
    {
        $user = $this->users->findByEmail($email);

        // Пароль проверяется и для несуществующего пользователя: время ответа не выдаёт, есть ли такой email.
        $hash = $user['password_hash'] ?? '$2y$10$usesomesillystringforsalt.udYr6gH3W1o7gGSYAUsV6mM8XBQi';
        $ok = password_verify($password, $hash);

        if ($ok && $user !== null) {
            // Если хеш создан устаревшими параметрами, он обновляется при успешном входе.
            if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                $this->users->updatePassword((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
            }
            $this->login($user);

            return true;
        }

        return false;
    }

    public function login(array $user): void
    {
        $this->session->regenerate();
        $this->csrf->refresh();
        $this->session->set('user_id', (int) $user['id']);
        $this->user = $user;
        $this->loaded = true;
    }

    public function logout(): void
    {
        $this->session->destroy();
        $this->user = null;
    }

    /** Сколько секунд вход ещё заблокирован после серии неудачных попыток (0 — можно входить). */
    public function lockedFor(): int
    {
        $until = (int) $this->session->get('login_locked_until', 0);

        return max(0, $until - time());
    }

    public function registerFailure(): void
    {
        $count = (int) $this->session->get('login_failures', 0) + 1;
        $this->session->set('login_failures', $count);

        if ($count >= (int) config('login_attempts', 5)) {
            $this->session->set('login_locked_until', time() + (int) config('login_lockout', 300));
            $this->session->set('login_failures', 0);
        }
    }

    public function clearFailures(): void
    {
        $this->session->forget('login_failures');
        $this->session->forget('login_locked_until');
    }
}
