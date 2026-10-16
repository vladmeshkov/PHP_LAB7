<?php
/**
 * Главная страница.
 * @var array $classes  код => название
 * @var array $summary  класс => [total, min_price, max_capacity]
 */
$texts = [
    'economy'  => 'Компактный номер для коротких поездок: всё необходимое для отдыха и работы.',
    'standard' => 'Просторный номер с двуспальной кроватью, кондиционером и сейфом.',
    'comfort'  => 'Улучшенный номер с видом на город, мини-баром и зоной отдыха.',
    'suite'    => 'Двухкомнатный люкс с гостиной, джакузи и панорамными окнами.',
];
?>
<section class="hero">
    <div class="container">
        <div class="eyebrow">Минск · центр города</div>
        <h1>Отдых, в который хочется вернуться</h1>
        <p class="lead">Выберите даты, и мы покажем свободные номера. Бронирование занимает минуту, отмена бесплатна до дня заезда.</p>
    </div>
</section>

<div class="container">
    <form class="searchbar" method="get" action="<?= e(url('rooms')) ?>">
        <div class="row g-3 align-items-end">
            <div class="col-6 col-lg-3">
                <label class="form-label" for="from">Заезд</label>
                <input class="form-control" type="date" id="from" name="from" min="<?= e(date('Y-m-d')) ?>" value="<?= e(date('Y-m-d', strtotime('+1 day'))) ?>">
            </div>
            <div class="col-6 col-lg-3">
                <label class="form-label" for="to">Выезд</label>
                <input class="form-control" type="date" id="to" name="to" min="<?= e(date('Y-m-d')) ?>" value="<?= e(date('Y-m-d', strtotime('+3 days'))) ?>">
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label" for="guests">Гостей</label>
                <select class="form-select" id="guests" name="guests">
                    <?php for ($i = 1; $i <= 6; $i++): ?><option value="<?= $i ?>"<?= $i === 2 ? ' selected' : '' ?>><?= $i ?></option><?php endfor; ?>
                </select>
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label" for="class">Класс</label>
                <select class="form-select" id="class" name="class">
                    <option value="">Любой</option>
                    <?php foreach ($classes as $code => $label): ?><option value="<?= e($code) ?>"><?= e($label) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-lg-2"><button class="btn btn-gold w-100">Найти номера</button></div>
        </div>
    </form>

    <section class="section">
        <div class="section-head">
            <div class="eyebrow">Номера</div>
            <h2>Выберите свой класс</h2>
            <p class="text-muted mb-0">Четыре класса номеров на шести этажах. Цена указана за сутки.</p>
        </div>
        <div class="row g-4">
            <?php foreach ($classes as $code => $label): ?>
                <?php $info = $summary[$code] ?? null; ?>
                <div class="col-md-6 col-xl-3">
                    <a class="card class-card text-decoration-none text-reset" href="<?= e(url('rooms?class=' . $code)) ?>">
                        <img src="<?= e(room_image($code)) ?>" alt="Номер класса «<?= e($label) ?>»" loading="lazy">
                        <div class="card-body">
                            <h3><?= e($label) ?></h3>
                            <p class="text-muted small mb-0"><?= e($texts[$code]) ?></p>
                            <?php if ($info): ?>
                                <div class="from-price">от <?= e(money($info['min_price'])) ?> <span>· <?= (int) $info['total'] ?> номеров</span></div>
                            <?php endif; ?>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="section pb-0">
        <div class="row g-4">
            <div class="col-md-6 col-lg-3"><div class="feature"><span class="feature-icon"><?= icon('wifi') ?></span><div><h3>Wi-Fi и работа</h3><p>Быстрый интернет и рабочий стол в каждом номере.</p></div></div></div>
            <div class="col-md-6 col-lg-3"><div class="feature"><span class="feature-icon"><?= icon('coffee') ?></span><div><h3>Завтрак</h3><p>Шведский стол с 7:00 до 10:30, в люксах – включён.</p></div></div></div>
            <div class="col-md-6 col-lg-3"><div class="feature"><span class="feature-icon"><?= icon('key') ?></span><div><h3>Заселение 24/7</h3><p>Стойка регистрации работает круглосуточно.</p></div></div></div>
            <div class="col-md-6 col-lg-3"><div class="feature"><span class="feature-icon"><?= icon('shield') ?></span><div><h3>Бесплатная отмена</h3><p>Отмените бронь до дня заезда, оплата вернётся.</p></div></div></div>
        </div>
    </section>
</div>
