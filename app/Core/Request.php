<?php
declare(strict_types=1);

namespace Hotel\Core;

/** Входящий HTTP-запрос: метод, путь, параметры. Единственное место, где читаются $_GET, $_POST и $_SERVER. */
final class Request
{
    private string $path;
    private string $basePath;

    public function __construct(
        private array $query,
        private array $post,
        private array $server
    ) {
        // Если сайт лежит не в корне (например, /lab7/public), эта часть адреса не участвует в маршрутизации.
        // На Windows dirname() возвращает «\» для корня, поэтому слэши приводятся к единому виду уже после него.
        $script = (string) ($server['SCRIPT_NAME'] ?? '');
        $this->basePath = rtrim(str_replace('\\', '/', dirname(str_replace('\\', '/', $script))), '/');

        $uri = (string) parse_url((string) ($server['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
        if ($this->basePath !== '' && str_starts_with($uri, $this->basePath)) {
            $uri = substr($uri, strlen($this->basePath));
        }
        $this->path = '/' . trim(rawurldecode($uri), '/');
    }

    public static function capture(): self
    {
        return new self($_GET, $_POST, $_SERVER);
    }

    public function method(): string
    {
        return strtoupper((string) ($this->server['REQUEST_METHOD'] ?? 'GET'));
    }

    public function path(): string
    {
        return $this->path;
    }

    public function basePath(): string
    {
        return $this->basePath;
    }

    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    /** Строка из POST (если нет — из GET); пробелы по краям убираются, массивы игнорируются. */
    public function input(string $key, string $default = ''): string
    {
        $value = $this->post[$key] ?? $this->query[$key] ?? $default;

        return is_string($value) ? trim($value) : $default;
    }

    public function query(string $key, string $default = ''): string
    {
        $value = $this->query[$key] ?? $default;

        return is_string($value) ? trim($value) : $default;
    }

    /** Значение без обрезки пробелов (нужно для паролей). */
    public function raw(string $key): string
    {
        $value = $this->post[$key] ?? '';

        return is_string($value) ? $value : '';
    }

    /** Все текстовые поля формы (без служебного _token). У паролей пробелы не обрезаются. */
    public function all(): array
    {
        $data = [];
        foreach ($this->post as $key => $value) {
            if (!is_string($value) || $key === '_token') {
                continue;
            }
            $data[$key] = str_contains($key, 'password') ? $value : trim($value);
        }

        return $data;
    }

    /** Все параметры адреса (для ссылок постраничной навигации). */
    public function queryAll(): array
    {
        return array_filter($this->query, 'is_string');
    }

    public function has(string $key): bool
    {
        return isset($this->post[$key]);
    }

    public function header(string $name): string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));

        return (string) ($this->server[$key] ?? '');
    }

    /**
     * Запрос пришёл с нашего же сайта? Сравниваются Origin (или Referer) и Host.
     * Если браузер заголовок не прислал, запрос пропускается: решает CSRF-токен.
     */
    public function sameOrigin(): bool
    {
        $source = $this->header('Origin') ?: $this->header('Referer');
        if ($source === '') {
            return true;
        }

        $host = (string) parse_url($source, PHP_URL_HOST);
        $port = parse_url($source, PHP_URL_PORT);
        $from = $port ? $host . ':' . $port : $host;

        return strcasecmp($from, $this->header('Host')) === 0;
    }

    public function ip(): string
    {
        return (string) ($this->server['REMOTE_ADDR'] ?? '');
    }
}
