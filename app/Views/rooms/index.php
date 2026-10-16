<?php
/**
 * @var array $rooms
 * @var \Hotel\Core\Paginator $paginator
 * @var array $filters
 * @var ?string $notice
 * @var bool $dated
 * @var int $nights
 * @var array $classes
 */
require_once __DIR__ . '/../partials/badges.php';
$query = static fn (array $extra = []): string => http_build_query(array_filter(['from' => $filters['from'], 'to' => $filters['to'], 'guests' => $filters['guests']] + $extra));
?>
<h1 class="page-title">Номера отеля</h1>
<p class="page-lead">Укажите даты проживания, и мы покажем только свободные номера.</p>

<form method="get" action="<?= e(url('rooms')) ?>" class="card card-body shadow-sm mb-4">
    <div class="row g-3 align-items-end">
        <div class="col-6 col-lg-2">
            <label class="form-label" for="from">Заезд</label>
            <input class="form-control" type="date" id="from" name="from" value="<?= e($filters['from']) ?>" min="<?= e(date('Y-m-d')) ?>">
        </div>
        <div class="col-6 col-lg-2">
            <label class="form-label" for="to">Выезд</label>
            <input class="form-control" type="date" id="to" name="to" value="<?= e($filters['to']) ?>" min="<?= e(date('Y-m-d')) ?>">
        </div>
        <div class="col-6 col-lg-2">
            <label class="form-label" for="guests">Гостей</label>
            <select class="form-select" id="guests" name="guests">
                <option value="">Любое</option>
                <?php for ($i = 1; $i <= 6; $i++): ?>
                    <option value="<?= $i ?>"<?= (string) $i === $filters['guests'] ? ' selected' : '' ?>><?= $i ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-6 col-lg-2">
            <label class="form-label" for="class">Класс</label>
            <select class="form-select" id="class" name="class">
                <option value="">Все классы</option>
                <?php foreach ($classes as $code => $label): ?>
                    <option value="<?= e($code) ?>"<?= $code === $filters['class'] ? ' selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-lg-2">
            <label class="form-label" for="sort">Сортировка</label>
            <select class="form-select" id="sort" name="sort">
                <option value="number"<?= $filters['sort'] === 'number' ? ' selected' : '' ?>>По номеру</option>
                <option value="price_asc"<?= $filters['sort'] === 'price_asc' ? ' selected' : '' ?>>Сначала дешёвые</option>
                <option value="price_desc"<?= $filters['sort'] === 'price_desc' ? ' selected' : '' ?>>Сначала дорогие</option>
            </select>
        </div>
        <div class="col-6 col-lg-2 d-flex gap-2">
            <button class="btn btn-primary flex-grow-1">Найти</button>
            <a class="btn btn-outline-secondary" href="<?= e(url('rooms')) ?>" title="Сбросить фильтры">×</a>
        </div>
    </div>
</form>

<?php if ($notice): ?><div class="alert alert-warning"><?= e($notice) ?></div><?php endif; ?>

<p class="text-muted">
    <?php if ($dated): ?>
        Свободные номера на <?= e(date_ru($filters['from'])) ?> – <?= e(date_ru($filters['to'])) ?> (<?= (int) $nights ?> сут.):
    <?php else: ?>
        Все номера отеля:
    <?php endif; ?>
    найдено <?= (int) $paginator->total ?>
</p>

<?php if ($rooms === []): ?>
    <div class="alert alert-secondary">По выбранным условиям свободных номеров нет. Измените даты или класс номера.</div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <?php foreach ($rooms as $room): ?>
        <div class="col-md-6 col-lg-4">
            <article class="card room-card h-100">
                <div class="room-band room-band--<?= e($room['class']) ?>">
                    <span class="room-band__number"><?= e($room['number']) ?></span>
                    <?= class_badge($room['class']) ?>
                </div>
                <div class="card-body d-flex flex-column">
                    <p class="text-muted small flex-grow-1 mb-3"><?= e(mb_strimwidth((string) $room['description'], 0, 110, '…')) ?></p>
                    <div class="d-flex justify-content-between align-items-end">
                        <div>
                            <div class="room-price"><?= e(money($room['price'])) ?> <span>/ сутки</span></div>
                            <?php if ($dated): ?>
                                <div class="small text-success">за <?= (int) $nights ?> сут.: <?= e(money($room['price'] * $nights)) ?></div>
                            <?php endif; ?>
                            <div class="small text-muted">до <?= (int) $room['capacity'] ?> гост.</div>
                        </div>
                        <a class="btn btn-outline-primary btn-sm" href="<?= e(url('rooms/' . $room['id'])) ?><?= $dated || $filters['guests'] ? '?' . e($query()) : '' ?>">Подробнее</a>
                    </div>
                </div>
            </article>
        </div>
    <?php endforeach; ?>
</div>

<?= $view->partial('pagination', ['paginator' => $paginator, 'request' => $request]) ?>
