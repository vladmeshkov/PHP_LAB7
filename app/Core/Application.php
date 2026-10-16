<?php
declare(strict_types=1);

namespace Hotel\Core;

use Hotel\Repositories\BookingRepository;
use Hotel\Repositories\PaymentRepository;
use Hotel\Repositories\RoomRepository;
use Hotel\Repositories\UserRepository;
use Hotel\Services\BookingService;
use PDO;
use Throwable;

/**
 * Ядро приложения: хранит настройки и общие объекты (запрос, сессия, БД, репозитории),
 * принимает запрос, находит маршрут, проверяет доступ и CSRF, вызывает контроллер и отправляет ответ.
 */
final class Application
{
    private static ?self $instance = null;

    private array $config = [];
    private Request $request;
    private Session $session;
    private ?Csrf $csrf = null;
    private ?Auth $auth = null;
    private ?View $view = null;
    private ?PDO $pdo = null;
    private array $services = [];

    private function __construct(private string $root)
    {
        $this->request = Request::capture();
        $this->session = new Session();
    }

    public static function boot(string $root): self
    {
        self::$instance = new self($root);
        $app = self::$instance;

        $app->config = ['app' => require $root . '/config/app.php', 'database' => require $root . '/config/database.php'];
        date_default_timezone_set((string) $app->config('app.timezone', 'UTC'));

        return $app;
    }

    public static function instance(): self
    {
        return self::$instance ?? throw new \LogicException('Приложение не запущено');
    }

    /** Значение настройки: config('app.per_page.rooms') ищет в config/app.php, config('database.host') в config/database.php. */
    public function config(string $key, mixed $default = null): mixed
    {
        // Ключи без префикса относятся к config/app.php
        $parts = explode('.', $key);
        if (!isset($this->config[$parts[0]])) {
            array_unshift($parts, 'app');
        }

        $value = $this->config;
        foreach ($parts as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }

        return $value;
    }

    public function root(): string
    {
        return $this->root;
    }

    public function request(): Request
    {
        return $this->request;
    }

    public function session(): Session
    {
        return $this->session;
    }

    public function csrf(): Csrf
    {
        return $this->csrf ??= new Csrf($this->session);
    }

    public function auth(): Auth
    {
        return $this->auth ??= new Auth($this->session, $this->csrf(), $this->users());
    }

    public function view(): View
    {
        return $this->view ??= new View($this->root . '/app/Views');
    }

    /** Подключение к БД создаётся при первом обращении. */
    public function db(): PDO
    {
        return $this->pdo ??= Database::connect($this->config['database']);
    }

    public function users(): UserRepository
    {
        return $this->services[UserRepository::class] ??= new UserRepository($this->db());
    }

    public function rooms(): RoomRepository
    {
        return $this->services[RoomRepository::class] ??= new RoomRepository($this->db());
    }

    public function bookings(): BookingRepository
    {
        return $this->services[BookingRepository::class] ??= new BookingRepository($this->db());
    }

    public function payments(): PaymentRepository
    {
        return $this->services[PaymentRepository::class] ??= new PaymentRepository($this->db());
    }

    public function bookingService(): BookingService
    {
        return $this->services[BookingService::class] ??= new BookingService($this->db(), $this->rooms(), $this->bookings(), $this->payments());
    }

    /** Обработка одного запроса от начала до конца. */
    public function run(): void
    {
        $this->session->start();

        try {
            $response = $this->handle();
        } catch (HttpException $e) {
            $response = $this->errorPage($e->status());
        } catch (Throwable $e) {
            $this->log($e);
            $response = $this->errorPage(500, $e);
        }

        $response->send();
    }

    private function handle(): Response
    {
        $router = new Router();
        (require $this->root . '/config/routes.php')($router);

        $route = $router->match($this->request->method(), $this->request->path());

        // Любой POST-запрос должен нести верный CSRF-токен.
        if ($this->request->isPost() && !$this->csrf()->valid($this->request)) {
            throw new HttpException(419);
        }

        foreach ($route['middleware'] as $rule) {
            if (($early = $this->guard($rule)) !== null) {
                return $early;
            }
        }

        [$class, $method] = $route['handler'];
        $result = (new $class($this))->$method($this->request, ...array_map('intval', $route['params']));

        return $result instanceof Response ? $result : new Response((string) $result);
    }

    /** Проверка доступа: auth (нужен вход), admin (нужна роль администратора), guest (только для гостей). */
    private function guard(string $rule): ?Response
    {
        $auth = $this->auth();

        if ($rule === 'auth' && !$auth->check()) {
            $this->session->flash('warning', 'Для этого действия нужно войти в систему');

            return Response::redirect(url('login'));
        }
        if ($rule === 'admin') {
            if (!$auth->check()) {
                return Response::redirect(url('login'));
            }
            if (!$auth->isAdmin()) {
                throw new HttpException(403);
            }
        }
        if ($rule === 'guest' && $auth->check()) {
            return Response::redirect(url('rooms'));
        }

        return null;
    }

    private function errorPage(int $status, ?Throwable $e = null): Response
    {
        $titles = [403 => 'Доступ запрещён', 404 => 'Страница не найдена', 405 => 'Метод не поддерживается', 419 => 'Страница устарела', 500 => 'Ошибка сервера'];
        $debug = (bool) $this->config('debug', false) && $e !== null;

        try {
            $html = $this->view()->render('errors/error', [
                'status'  => $status,
                'title'   => $titles[$status] ?? 'Ошибка',
                'details' => $debug ? $e::class . ': ' . $e->getMessage() : '',
                'user'    => null,
                'flashes' => [],
                'errors'  => [],
                'request' => $this->request,
                'view'    => $this->view(),
            ]);
        } catch (Throwable) {
            $html = '<h1>Ошибка ' . $status . '</h1>';
        }

        return new Response($html, $status);
    }

    /** Подробности ошибки сохраняются в журнал, а пользователю показывается только общее сообщение. */
    private function log(Throwable $e): void
    {
        $line = sprintf("[%s] %s: %s in %s:%d\n", date('Y-m-d H:i:s'), $e::class, $e->getMessage(), $e->getFile(), $e->getLine());
        @file_put_contents($this->root . '/storage/logs/app.log', $line, FILE_APPEND | LOCK_EX);
    }
}
