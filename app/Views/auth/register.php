<?php /** @var array $errors */ ?>
<?php
$fields = [
    ['name', 'Имя', 'text', 'name'],
    ['email', 'Email', 'email', 'email'],
    ['password', 'Пароль', 'password', 'new-password'],
    ['password_confirmation', 'Повторите пароль', 'password', 'new-password'],
];
?>
<div class="card auth-card">
    <div class="auth-side">
        <div>
            <div class="eyebrow">Новый гость</div>
            <h2>Бронируйте номера за минуту</h2>
            <p>Регистрация открывает бронирование, историю проживаний и бесплатную отмену до дня заезда.</p>
        </div>
        <img src="<?= e(asset('img/rooms/comfort.svg')) ?>" alt="">
    </div>
    <div class="auth-form">
        <h1 class="h3 mb-4">Регистрация</h1>
        <form method="post" action="<?= e(url('register')) ?>" novalidate>
            <?= csrf_field() ?>
            <?php foreach ($fields as [$name, $label, $type, $auto]): ?>
                <?php $errorKey = $name === 'password_confirmation' ? 'password' : $name; ?>
                <div class="mb-3">
                    <label class="form-label" for="<?= e($name) ?>"><?= e($label) ?></label>
                    <input class="form-control<?= isset($errors[$errorKey]) && $name !== 'password_confirmation' ? ' is-invalid' : '' ?>"
                           type="<?= e($type) ?>" id="<?= e($name) ?>" name="<?= e($name) ?>" autocomplete="<?= e($auto) ?>"
                           <?= $type === 'password' ? '' : 'value="' . old($name) . '"' ?> required>
                    <?php if (isset($errors[$name])): ?><div class="invalid-feedback"><?= e($errors[$name]) ?></div><?php endif; ?>
                </div>
            <?php endforeach; ?>
            <div class="form-text mb-3">Пароль: не короче 8 символов, буквы и цифры.</div>
            <button class="btn btn-primary w-100 py-2">Зарегистрироваться</button>
        </form>
        <p class="text-center text-muted mt-4 mb-0">Уже есть аккаунт? <a href="<?= e(url('login')) ?>">Войти</a></p>
    </div>
</div>
