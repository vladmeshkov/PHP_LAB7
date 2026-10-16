<?php
/**
 * @var array $summary
 * @var array $byClass
 * @var array $byStatus
 * @var array $latest
 * @var array $classes
 */
$today = date('Y-m-d');
$cards = [
    ['users', 'Пользователей', (string) $summary['users']],
    ['bed', 'Номеров в продаже', (string) $summary['rooms']],
    ['calendar', 'Действующих броней', (string) $summary['active']],
    ['wallet', 'Выручка', money($summary['revenue'])],
    ['chart', 'Загрузка на 30 дней', $summary['occupancy'] . '%'],
];
$maxRevenue = max(1, ...array_map(static fn (array $r): float => (float) $r['revenue'], $byClass));
?>
<?= $view->partial('admin_nav', ['request' => $request]) ?>

<div class="row g-3 mb-4">
    <?php foreach ($cards as [$icon, $label, $value]): ?>
        <div class="col-6 col-xl">
            <div class="card stat h-100">
                <span class="stat-icon"><?= icon($icon) ?></span>
                <div><div class="stat-label"><?= e($label) ?></div><div class="stat-value"><?= e($value) ?></div></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card card-body h-100">
            <h3 class="mb-3">По классам номеров</h3>
            <?php foreach ($byClass as $row): ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span><?= class_badge($row['class']) ?> <span class="text-muted small ms-1"><?= (int) $row['rooms'] ?> ном. · <?= (int) $row['bookings'] ?> броней</span></span>
                        <strong class="text-nowrap"><?= e(money($row['revenue'])) ?></strong>
                    </div>
                    <div class="bar"><i class="w-<?= (int) round(100 * (float) $row['revenue'] / $maxRevenue / 5) * 5 ?>"></i></div>
                </div>
            <?php endforeach; ?>
            <p class="small text-muted mb-0">Подтверждённых броней: <?= (int) ($byStatus['confirmed'] ?? 0) ?>, отменённых: <?= (int) ($byStatus['cancelled'] ?? 0) ?>. Выручка считается по оплатам без возвратов.</p>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card table-card h-100">
            <div class="p-3"><h3 class="mb-0">Последние бронирования</h3></div>
            <div class="table-responsive">
                <table class="table table-compact align-middle">
                    <thead><tr><th>Гость</th><th>Номер</th><th>Даты</th><th>Статус</th></tr></thead>
                    <tbody>
                        <?php foreach ($latest as $b): ?>
                            <tr>
                                <td class="text-nowrap"><?= e($b['user_name']) ?></td>
                                <td><?= e($b['room_number']) ?></td>
                                <td class="text-nowrap"><?= e(date_ru($b['date_from'])) ?> – <?= e(date_ru($b['date_to'])) ?></td>
                                <td><?= status_badge($b, $today) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
