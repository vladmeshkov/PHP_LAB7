<?php
/**
 * @var array $summary
 * @var array $byClass
 * @var array $byStatus
 * @var array $latest
 * @var array $classes
 */
require_once __DIR__ . '/../partials/badges.php';
$today = date('Y-m-d');
$cards = [
    ['Пользователей', (string) $summary['users']],
    ['Номеров в продаже', (string) $summary['rooms']],
    ['Действующих броней', (string) $summary['active']],
    ['Выручка', money($summary['revenue'])],
    ['Загрузка на 30 дней', $summary['occupancy'] . '%'],
];
?>
<?= $view->partial('admin_nav', ['request' => $request]) ?>

<div class="row g-3 mb-4">
    <?php foreach ($cards as [$label, $value]): ?>
        <div class="col-6 col-lg">
            <div class="card shadow-sm h-100"><div class="card-body">
                <div class="text-muted small"><?= e($label) ?></div>
                <div class="fs-4 fw-semibold"><?= e($value) ?></div>
            </div></div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card shadow-sm"><div class="card-body">
            <h2 class="h6">По классам номеров</h2>
            <table class="table table-sm mb-0">
                <thead><tr><th>Класс</th><th class="text-end">Номеров</th><th class="text-end">Броней</th><th class="text-end">Выручка</th></tr></thead>
                <tbody>
                    <?php foreach ($byClass as $row): ?>
                        <tr>
                            <td><?= class_badge($row['class']) ?></td>
                            <td class="text-end"><?= (int) $row['rooms'] ?></td>
                            <td class="text-end"><?= (int) $row['bookings'] ?></td>
                            <td class="text-end text-nowrap"><?= e(money($row['revenue'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p class="small text-muted mt-3 mb-0">
                Подтверждённых броней: <?= (int) ($byStatus['confirmed'] ?? 0) ?>, отменённых: <?= (int) ($byStatus['cancelled'] ?? 0) ?>.
                Выручка считается по оплатам без возвратов.
            </p>
        </div></div>
    </div>
    <div class="col-lg-7">
        <div class="card shadow-sm"><div class="card-body">
            <h2 class="h6">Последние бронирования</h2>
            <div class="table-responsive"><table class="table table-sm mb-0">
                <thead><tr><th>№</th><th>Гость</th><th>Номер</th><th>Даты</th><th>Статус</th></tr></thead>
                <tbody>
                    <?php foreach ($latest as $b): ?>
                        <tr>
                            <td><?= (int) $b['id'] ?></td>
                            <td class="text-nowrap"><?= e($b['user_name']) ?></td>
                            <td><?= e($b['room_number']) ?></td>
                            <td class="text-nowrap"><?= e(date_ru($b['date_from'])) ?> – <?= e(date_ru($b['date_to'])) ?></td>
                            <td><?= status_badge($b, $today) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table></div>
        </div></div>
    </div>
</div>
