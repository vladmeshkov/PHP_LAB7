<?php
/**
 * @var array $room
 * @var array $classes
 * @var array $methods
 * @var array $busy
 * @var string $from
 * @var string $to
 * @var int $guests
 * @var ?array $user
 * @var array $errors
 */
?>
<nav aria-label="breadcrumb"><ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?= e(url('rooms')) ?>">Номера</a></li>
    <li class="breadcrumb-item active">Номер <?= e($room['number']) ?></li>
</ol></nav>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="room-hero mb-4">
            <img src="<?= e(room_image($room['class'])) ?>" alt="Номер <?= e($room['number']) ?>">
        </div>

        <div class="d-flex flex-wrap align-items-center gap-3 mb-2">
            <h1 class="mb-0">Номер <?= e($room['number']) ?></h1>
            <?= class_badge($room['class']) ?>
        </div>
        <p class="text-muted"><?= icon('users') ?> до <?= (int) $room['capacity'] ?> гост. · <?= e(money($room['price'])) ?> за сутки</p>
        <p class="mb-4"><?= nl2br(e((string) $room['description'])) ?></p>

        <div class="card card-body mb-3">
            <h3 class="mb-3">В номере</h3>
            <ul class="amenities">
                <?php foreach (room_amenities($room['class']) as $item): ?>
                    <li><?= icon('check') ?><?= e($item) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <?php if ($busy): ?>
            <div class="card card-body">
                <h3 class="mb-3">Ближайшие занятые даты</h3>
                <ul class="mb-0 ps-3">
                    <?php foreach ($busy as $period): ?>
                        <li><?= e(date_ru($period['date_from'])) ?> – <?= e(date_ru($period['date_to'])) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-5">
        <div class="card booking-panel">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-baseline mb-3">
                    <div class="price-big"><?= e(money($room['price'])) ?></div>
                    <span class="text-muted">за сутки</span>
                </div>

                <?php if (isset($errors['form'])): ?>
                    <div class="alert alert-danger"><?= e($errors['form']) ?></div>
                <?php endif; ?>

                <?php if ($user && $user['role'] === 'admin'): ?>
                    <p class="text-muted mb-0">Администратор бронирует номера не от своего имени. Бронирования клиентов – в разделе «Админ-панель».</p>
                <?php elseif (!$user): ?>
                    <p class="text-muted">Чтобы забронировать номер, войдите или создайте аккаунт.</p>
                    <div class="d-flex gap-2">
                        <a class="btn btn-primary flex-grow-1" href="<?= e(url('login')) ?>">Войти</a>
                        <a class="btn btn-outline-primary flex-grow-1" href="<?= e(url('register')) ?>">Регистрация</a>
                    </div>
                <?php else: ?>
                    <form method="post" action="<?= e(url('bookings')) ?>" id="booking-form" data-price="<?= e($room['price']) ?>" novalidate>
                        <?= csrf_field() ?>
                        <input type="hidden" name="room_id" value="<?= (int) $room['id'] ?>">

                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label" for="date_from">Заезд</label>
                                <input class="form-control<?= isset($errors['date_from']) ? ' is-invalid' : '' ?>" type="date" id="date_from" name="date_from" value="<?= old('date_from', $from) ?>" min="<?= e(date('Y-m-d')) ?>" required>
                                <?php if (isset($errors['date_from'])): ?><div class="invalid-feedback"><?= e($errors['date_from']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="date_to">Выезд</label>
                                <input class="form-control<?= isset($errors['date_to']) ? ' is-invalid' : '' ?>" type="date" id="date_to" name="date_to" value="<?= old('date_to', $to) ?>" min="<?= e(date('Y-m-d')) ?>" required>
                                <?php if (isset($errors['date_to'])): ?><div class="invalid-feedback"><?= e($errors['date_to']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="guests">Гостей</label>
                                <input class="form-control<?= isset($errors['guests']) ? ' is-invalid' : '' ?>" type="number" id="guests" name="guests" min="1" max="<?= (int) $room['capacity'] ?>" value="<?= old('guests', (string) $guests) ?>" required>
                                <?php if (isset($errors['guests'])): ?><div class="invalid-feedback"><?= e($errors['guests']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="method">Оплата</label>
                                <select class="form-select<?= isset($errors['method']) ? ' is-invalid' : '' ?>" id="method" name="method" required>
                                    <?php foreach ($methods as $code => $label): ?>
                                        <option value="<?= e($code) ?>"<?= old('method', 'card') === $code ? ' selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="summary-row mt-3">
                            <span class="text-muted">Итого за <span id="nights">–</span> сут.</span>
                            <strong id="total">–</strong>
                        </div>
                        <button class="btn btn-gold w-100 py-2">Забронировать и оплатить</button>
                        <div class="form-text text-center mt-2">Оплата условная: данные карты не запрашиваются.</div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
