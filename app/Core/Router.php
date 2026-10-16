<?php
declare(strict_types=1);

namespace Hotel\Core;

/**
 * Маршрутизатор: сопоставляет метод и путь запроса с методом контроллера.
 * Параметры в пути записываются как {id} и принимают только числа.
 */
final class Router
{
    /** @var array<int, array{method: string, regex: string, handler: array, middleware: string[]}> */
    private array $routes = [];

    public function get(string $pattern, array $handler, array $middleware = []): void
    {
        $this->add('GET', $pattern, $handler, $middleware);
    }

    public function post(string $pattern, array $handler, array $middleware = []): void
    {
        $this->add('POST', $pattern, $handler, $middleware);
    }

    private function add(string $method, string $pattern, array $handler, array $middleware): void
    {
        $regex = preg_replace('#\{(\w+)\}#', '(?P<$1>\d+)', $pattern);
        $this->routes[] = [
            'method'     => $method,
            'regex'      => '#^' . $regex . '$#',
            'handler'    => $handler,
            'middleware' => $middleware,
        ];
    }

    /**
     * @return array{handler: array, params: array<string, string>, middleware: string[]}
     * @throws HttpException 404, если пути нет; 405, если путь есть, но для другого метода
     */
    public function match(string $method, string $path): array
    {
        $pathExists = false;

        foreach ($this->routes as $route) {
            if (preg_match($route['regex'], $path, $m) !== 1) {
                continue;
            }
            $pathExists = true;
            if ($route['method'] !== $method) {
                continue;
            }

            $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);

            return ['handler' => $route['handler'], 'params' => $params, 'middleware' => $route['middleware']];
        }

        throw new HttpException($pathExists ? 405 : 404);
    }
}
