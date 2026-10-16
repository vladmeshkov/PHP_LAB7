<?php
declare(strict_types=1);

namespace Hotel\Core;

/** Ответ сервера. К каждому ответу добавляются заголовки безопасности. */
final class Response
{
    private array $headers = [];

    public function __construct(private string $body = '', private int $status = 200)
    {
    }

    public static function redirect(string $location, int $status = 303): self
    {
        $response = new self('', $status);
        $response->headers['Location'] = $location;

        return $response;
    }

    public function withHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;

        return $this;
    }

    public function send(): void
    {
        http_response_code($this->status);

        $defaults = [
            'Content-Type'            => 'text/html; charset=utf-8',
            'X-Content-Type-Options'  => 'nosniff',
            'X-Frame-Options'         => 'DENY',
            'Referrer-Policy'         => 'same-origin',
            // Скрипты и стили только с нашего сайта: это ограничивает последствия XSS.
            'Content-Security-Policy' => "default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; frame-ancestors 'none'",
        ];
        foreach ($this->headers + $defaults as $name => $value) {
            header($name . ': ' . $value);
        }

        echo $this->body;
    }
}
