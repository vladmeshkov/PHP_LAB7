<?php
/**
 * Общий макет страницы.
 * @var string $content  HTML страницы
 * @var ?array $user     текущий пользователь
 * @var array  $flashes  одноразовые сообщения
 */
$path = $request->path();
$active = static fn (string $prefix): string => str_starts_with($path, $prefix) ? ' active' : '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($pageTitle ?? '') !== '' ? $pageTitle . ' – ' : '') ?><?= e(config('name')) ?></title>
    <link rel="icon" href="data:,">
    <link rel="stylesheet" href="<?= e(asset('vendor/bootstrap/bootstrap.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-lg site-nav">
    <div class="container">
        <a class="navbar-brand" href="<?= e(url('rooms')) ?>">
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

<main class="container py-4 flex-grow-1">
    <?php foreach ($flashes as $flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
            <?= e($flash['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Закрыть"></button>
        </div>
    <?php endforeach; ?>

    <?= $content ?>
</main>

<footer class="site-footer">
    <div class="container d-flex flex-wrap justify-content-between gap-2">
        <span class="brand"><?= e(config('name')) ?></span>
        <span>г. Минск, пр. Независимости, 10 · +375 17 000-00-00</span>
        <span>Учебный проект · PHP + MySQL (PDO)</span>
    </div>
</footer>

<script src="<?= e(asset('vendor/bootstrap/bootstrap.bundle.min.js')) ?>"></script>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
