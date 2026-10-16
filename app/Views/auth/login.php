<?php /** @var array $errors */ ?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card">
            <div class="card-body p-4">
                <h1 class="mb-4">Вход</h1>

                <?php if (isset($errors['form'])): ?>
                    <div class="alert alert-danger"><?= e($errors['form']) ?></div>
                <?php endif; ?>

                <form method="post" action="<?= e(url('login')) ?>" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input class="form-control" type="email" id="email" name="email" value="<?= old('email') ?>" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Пароль</label>
                        <input class="form-control" type="password" id="password" name="password" required>
                    </div>
                    <button class="btn btn-primary w-100">Войти</button>
                </form>
                <p class="text-center text-muted mt-3 mb-0">Нет аккаунта? <a href="<?= e(url('register')) ?>">Зарегистрируйтесь</a></p>
            </div>
        </div>
    </div>
</div>
