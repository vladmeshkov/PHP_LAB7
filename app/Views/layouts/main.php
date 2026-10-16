<?php
/**
 * Общий макет страницы.
 * @var string $content  HTML страницы
 * @var ?array $user     текущий пользователь
 * @var array  $flashes  одноразовые сообщения
 */
require_once __DIR__ . '/../partials/badges.php';

$path = $request->path();
$active = static fn (string $prefix): string => str_starts_with($path, $prefix) ? ' active' : '';
$isHome = $path === '/';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($pageTitle ?? '') !== '' ? $pageTitle . ' – ' : '') ?><?= e(config('name')) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= e(asset('img/favicon.svg')) ?>">
    <link rel="stylesheet" href="<?= e(asset('vendor/bootstrap/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-lg site-nav">
    <div class="container">
        <a class="navbar-brand" href="<?= e(url('')) ?>">
            <span class="brand-mark"><svg viewBox="0 0 64 64" aria-hidden="true"><path d="M19 18h7v10h12V18h7v28h-7V35H26v11h-7z"/></svg></span>
            <?= e(config('name')) ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav" aria-expanded="false" aria-label="Меню">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav me-auto ms-lg-4 gap-lg-1">
                <li class="nav-item"><a class="nav-link<?= $active('/rooms') ?>" href="<?= e(url('rooms')) ?>">Номера</a></li>
                <?php if ($user && $user['role'] !== 'admin'): ?>
                    <li class="nav-item"><a class="nav-link<?= $active('/bookings') ?>" href="<?= e(url('bookings')) ?>">Мои бронирования</a></li>
                <?php endif; ?>
                <?php if ($user && $user['role'] === 'admin'): ?>
                    <li class="nav-item"><a class="nav-link<?= $active('/admin') ?>" href="<?= e(url('admin')) ?>">Админ-панель</a></li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav align-items-lg-center gap-2">
                <?php if ($user): ?>
                    <li class="nav-item"><span class="user-chip"><span class="avatar"><?= e(initials($user['name'])) ?></span><?= e($user['name']) ?></span></li>
                    <li class="nav-item">
                        <form method="post" action="<?= e(url('logout')) ?>" class="m-0">
                            <?= csrf_field() ?>
                            <button class="btn btn-outline-primary btn-sm">Выйти</button>
                        </form>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('login')) ?>">Войти</a></li>
                    <li class="nav-item"><a class="btn btn-primary btn-sm" href="<?= e(url('register')) ?>">Регистрация</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<?php if ($flashes): ?>
    <div class="container pt-3">
        <?php foreach ($flashes as $flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show mb-2" role="alert">
                <?= e($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Закрыть"></button>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($isHome): ?>
    <?= $content ?>
<?php else: ?>
    <main class="container py-4 flex-grow-1">
        <?= $content ?>
    </main>
<?php endif; ?>

<footer class="site-footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-5">
                <div class="brand mb-2"><?= e(config('name')) ?></div>
                <p class="mb-0">Комфортный отель в центре города: номера четырёх классов, завтрак и круглосуточная стойка регистрации.</p>
            </div>
            <div class="col-6 col-md-3">
                <h4>Разделы</h4>
                <div><a href="<?= e(url('rooms')) ?>">Номера</a></div>
                <div><a href="<?= e(url('login')) ?>">Личный кабинет</a></div>
            </div>
            <div class="col-6 col-md-4">
                <h4>Контакты</h4>
                <div><?= icon('map') ?> г. Минск, пр. Независимости, 10</div>
                <div><?= icon('phone') ?> +375 17 000-00-00</div>
            </div>
        </div>
        <div class="copy">Учебный проект · PHP 8, MySQL (PDO), Bootstrap 5</div>
    </div>
</footer>

<script src="<?= e(asset('vendor/bootstrap/bootstrap.bundle.min.js')) ?>"></script>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
