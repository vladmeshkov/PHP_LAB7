<?php
/**
 * @var array $rooms
 * @var \Hotel\Core\Paginator $paginator
 * @var array $filters
 * @var array $classes
 */
require_once __DIR__ . '/../../partials/badges.php';
?>
<?= $view->partial('admin_nav', ['request' => $request]) ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0">Номера <span class="text-muted fs-6">(<?= (int) $paginator->total ?>)</span></h1>
    <a class="btn btn-primary" href="<?= e(url('admin/rooms/create')) ?>">Добавить номер</a>
</div>

<form method="get" class="row g-2 mb-3">
    <div class="col-sm-4 col-lg-3"><input class="form-control" name="q" placeholder="Поиск по номеру" value="<?= e($filters['q']) ?>"></div>
    <div class="col-sm-4 col-lg-3">
        <select class="form-select" name="class">
            <option value="">Все классы</option>
            <?php foreach ($classes as $code => $label): ?>
                <option value="<?= e($code) ?>"<?= $code === $filters['class'] ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-sm-4 col-lg-3">
        <select class="form-select" name="sort">
            <option value="number"<?= $filters['sort'] === 'number' ? ' selected' : '' ?>>По номеру</option>
            <option value="price_asc"<?= $filters['sort'] === 'price_asc' ? ' selected' : '' ?>>Сначала дешёвые</option>
            <option value="price_desc"<?= $filters['sort'] === 'price_desc' ? ' selected' : '' ?>>Сначала дорогие</option>
        </select>
    </div>
    <div class="col-auto"><button class="btn btn-outline-primary">Применить</button></div>
</form>

<div class="table-responsive card table-card">
    <table class="table align-middle mb-0">
        <thead class="table-light"><tr><th>Номер</th><th>Класс</th><th class="text-end">Цена</th><th class="text-center">Мест</th><th>Статус</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($rooms as $room): ?>
                <tr>
                    <td class="fw-semibold"><?= e($room['number']) ?></td>
                    <td><?= class_badge($room['class']) ?></td>
                    <td class="text-end"><?= e(money($room['price'])) ?></td>
                    <td class="text-center"><?= (int) $room['capacity'] ?></td>
                    <td><?= (int) $room['is_active'] ? '<span class="pill pill--ok">В продаже</span>' : '<span class="pill pill--done">Снят с продажи</span>' ?></td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('admin/rooms/' . $room['id'] . '/edit')) ?>">Изменить</a>
                        <form class="d-inline" method="post" action="<?= e(url('admin/rooms/' . $room['id'] . '/delete')) ?>" data-confirm="Удалить номер <?= e($room['number']) ?>?">
                            <?= csrf_field() ?>
                            <button class="btn btn-outline-danger btn-sm">Удалить</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($rooms === []): ?><tr><td colspan="6" class="text-center text-muted py-4">Ничего не найдено</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<div class="mt-4"><?= $view->partial('pagination', ['paginator' => $paginator, 'request' => $request]) ?></div>
