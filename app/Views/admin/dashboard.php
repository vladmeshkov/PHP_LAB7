<?php
/**
 * @var array $summary
 * @var array $byClass
 * @var array $byStatus
 * @var array $latest
 * @var array $classes
 */
use Hotel\Services\BookingStatus;

$cards = [
    ['Пользователей', (string) $summary['users']],
    ['Номеров в продаже', (string) $summary['rooms']],
    ['Действующих броней', (string) $summary['active']],
    ['Получено оплат', money($summary['revenue'])],
    ['Ожидает оплаты', money($summary['pending'])],
    ['Загрузка на 30 дней', $summary['occupancy'] . '%'],
];
?>
<?= $view->partial('admin_nav', ['request' => $request]) ?>

<div class="row g-3 mb-4">
    <?php foreach ($cards as [$label, $value]): ?>
        <div class="col-6 col-lg-4">
            <div class="card stat h-100"><div class="stat-label"><?= e($label) ?></div><div class="stat-value"><?= e($value) ?></div></div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card card-body mb-4">
            <h3 class="mb-3">Бронирования по статусам</h3>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach (BookingStatus::LABELS as $code => $label): ?>
                    <a class="text-decoration-none" href="<?= e(url('admin/bookings?status=' . $code)) ?>">
                        <?= status_badge(['status' => $code]) ?> <strong><?= (int) ($byStatus[$code] ?? 0) ?></strong>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="card table-card">
            <div class="p-3 pb-2"><h3 class="mb-0">По классам номеров</h3></div>
            <table class="table">
                <thead><tr><th>Класс</th><th class="text-end">Номеров</th><th class="text-end">Броней</th><th class="text-end">Оплачено</th></tr></thead>
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
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card table-card">
            <div class="p-3 pb-2 d-flex justify-content-between align-items-center">
                <h3 class="mb-0">Последние бронирования</h3>
                <a href="<?= e(url('admin/bookings')) ?>" class="small">Все</a>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Гость</th><th>Даты</th><th>Статус</th></tr></thead>
                    <tbody>
                        <?php foreach ($latest as $b): ?>
                            <tr>
                                <td class="text-nowrap"><?= e($b['user_name']) ?><span class="sub">номер <?= e($b['room_number']) ?></span></td>
                                <td class="text-nowrap"><?= e(date_ru($b['date_from'])) ?> – <?= e(date_ru($b['date_to'])) ?></td>
                                <td><?= status_badge($b) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
