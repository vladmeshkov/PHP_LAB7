<?php
declare(strict_types=1);

namespace Hotel\Core;

/**
 * Защита от CSRF. Токен хранится в сессии и добавляется в каждую форму скрытым полем;
 * чужой сайт не может его узнать, поэтому не сможет отправить запрос от имени пользователя.
 */
final class Csrf
{
    public function __construct(private Session $session)
    {
    }

    public function token(): string
    {
        $token = $this->session->get('_csrf');
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            $this->session->set('_csrf', $token);
        }

        return $token;
    }

    public function valid(Request $request): bool
    {
        $sent = $request->input('_token') ?: $request->header('X-CSRF-Token');

        return $sent !== '' && hash_equals($this->token(), $sent);   // сравнение за постоянное время
    }

    /** После входа и выхода токен меняется. */
    public function refresh(): void
    {
        $this->session->forget('_csrf');
    }
}
