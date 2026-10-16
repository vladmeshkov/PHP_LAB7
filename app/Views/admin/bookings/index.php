<?php
/**
 * @var array $bookings
 * @var \Hotel\Core\Paginator $paginator
 * @var string $status
 * @var string $search
 * @var string $today
 */
require_once __DIR__ . '/../../partials/badges.php';
?>
<?= $view->partial('admin_nav', ['request' => $request]) ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0">Бронирования <span class="text-muted fs-6">(<?= (int) $paginator->total ?>)</span></h1>
    <form method="get" class="d-flex gap-2">
        <input class="form-control" name="q" placeholder="Гость или номер" value="<?= e($search) ?>">
        <select class="form-select" name="status" aria-label="Статус">
            <option value="">Все статусы</option>
            <option value="confirmed"<?= $status === 'confirmed' ? ' selected' : '' ?>>Подтверждённые</option>
            <option value="cancelled"<?= $status === 'cancelled' ? ' selected' : '' ?>>Отменённые</option>
        </select>
        <button class="btn btn-outline-primary">Найти</button>
    </form>
</div>

<div class="table-responsive card table-card">
    <table class="table align-middle mb-0">
        <thead class="table-light"><tr><th>№</th><th>Гость</th><th>Номер</th><th>Даты</th><th class="text-end">Сумма</th><th>Статус</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($bookings as $b): ?>
                <tr>
                    <td><?= (int) $b['id'] ?></td>
                    <td><?= e($b['user_name']) ?><br><span class="small text-muted"><?= e($b['user_email']) ?></span></td>
                    <td><?= e($b['room_number']) ?> <?= class_badge($b['room_class']) ?></td>
                    <td class="text-nowrap"><?= e(date_ru($b['date_from'])) ?> – <?= e(date_ru($b['date_to'])) ?></td>
                    <td class="text-end"><?= e(money($b['amount'] ?? 0)) ?></td>
                    <td><?= status_badge($b, $today) ?></td>
                    <td class="text-end">
                        <?php if ($b['status'] === 'confirmed' && $b['date_to'] >= $today): ?>
                            <form method="post" action="<?= e(url('admin/bookings/' . $b['id'] . '/cancel')) ?>" data-confirm="Отменить бронирование №<?= (int) $b['id'] ?>?">
                                <?= csrf_field() ?>
                                <button class="btn btn-outline-danger btn-sm">Отменить</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($bookings === []): ?><tr><td colspan="7" class="text-center text-muted py-4">Ничего не найдено</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<div class="mt-4"><?= $view->partial('pagination', ['paginator' => $paginator, 'request' => $request]) ?></div>
