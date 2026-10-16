<?php /** @var array $errors */ ?>
<div class="card auth-card">
    <div class="auth-side">
        <div>
            <div class="eyebrow">С возвращением</div>
            <h2>Ваши бронирования всегда под рукой</h2>
            <p>Войдите, чтобы посмотреть историю проживаний или забронировать номер.</p>
        </div>
        <img src="<?= e(asset('img/rooms/suite.svg')) ?>" alt="">
    </div>
    <div class="auth-form">
        <h1 class="h3 mb-4">Вход</h1>

        <?php if (isset($errors['form'])): ?>
            <div class="alert alert-danger"><?= e($errors['form']) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= e(url('login')) ?>" novalidate>
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label" for="email">Email</label>
                <input class="form-control" type="email" id="email" name="email" value="<?= old('email') ?>" required autofocus>
            </div>
            <div class="mb-4">
                <label class="form-label" for="password">Пароль</label>
                <input class="form-control" type="password" id="password" name="password" required>
            </div>
            <button class="btn btn-primary w-100 py-2">Войти</button>
        </form>
        <p class="text-center text-muted mt-4 mb-0">Нет аккаунта? <a href="<?= e(url('register')) ?>">Зарегистрируйтесь</a></p>
    </div>
</div>
