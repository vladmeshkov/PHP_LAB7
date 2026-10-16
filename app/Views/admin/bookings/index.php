<?php
/**
 * @var array $bookings
 * @var \Hotel\Core\Paginator $paginator
 * @var string $status
 * @var string $search
 * @var string $today
 */
use Hotel\Services\BookingStatus;
?>
<?= $view->partial('admin_nav', ['request' => $request]) ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="mb-0">Бронирования <span class="text-muted fs-6">(<?= (int) $paginator->total ?>)</span></h1>
    <form method="get" class="d-flex gap-2">
        <input class="form-control" name="q" placeholder="Гость или номер" value="<?= e($search) ?>">
        <select class="form-select" name="status" aria-label="Статус">
            <option value="">Все статусы</option>
            <?php foreach (BookingStatus::LABELS as $code => $label): ?>
                <option value="<?= e($code) ?>"<?= $status === $code ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-outline-primary">Найти</button>
    </form>
</div>

<div class="table-responsive card table-card">
    <table class="table align-middle">
        <thead><tr><th>№</th><th>Гость</th><th>Номер</th><th>Даты</th><th class="text-end">Сумма</th><th>Статус</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($bookings as $b): ?>
                <tr>
                    <td><?= (int) $b['id'] ?></td>
                    <td><?= e($b['user_name']) ?><span class="sub"><?= e($b['user_email']) ?></span></td>
                    <td><?= e($b['room_number']) ?> <?= class_badge($b['room_class']) ?></td>
                    <td class="text-nowrap"><?= e(date_ru($b['date_from'])) ?> – <?= e(date_ru($b['date_to'])) ?></td>
                    <td class="text-end text-nowrap"><?= e(money($b['amount'] ?? 0)) ?></td>
                    <td><?= status_badge($b) ?><span class="sub"><?= e(cancel_note($b) ?: payment_note($b)) ?></span></td>
                    <td>
                        <div class="actions">
                            <?php if ($b['status'] === BookingStatus::BOOKED && $b['pay_status'] === 'pending'): ?>
                                <form method="post" action="<?= e(url('admin/bookings/' . $b['id'] . '/pay')) ?>" data-confirm="Принять оплату <?= e(money($b['amount'])) ?> по бронированию №<?= (int) $b['id'] ?>?">
                                    <?= csrf_field() ?><button class="btn btn-success btn-sm">Принять оплату</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($b['status'] === BookingStatus::PAID && $b['date_from'] <= $today): ?>
                                <form method="post" action="<?= e(url('admin/bookings/' . $b['id'] . '/check-in')) ?>">
                                    <?= csrf_field() ?><button class="btn btn-primary btn-sm">Заселить</button>
                                </form>
                            <?php endif; ?>
                            <?php if ($b['status'] === BookingStatus::CHECKED_IN): ?>
                                <form method="post" action="<?= e(url('admin/bookings/' . $b['id'] . '/check-out')) ?>" data-confirm="Выселить гостя по бронированию №<?= (int) $b['id'] ?>?">
                                    <?= csrf_field() ?><button class="btn btn-outline-primary btn-sm">Выселить</button>
                                </form>
                            <?php endif; ?>
                            <?php if (in_array($b['status'], BookingStatus::CANCELLABLE, true)): ?>
                                <form method="post" action="<?= e(url('admin/bookings/' . $b['id'] . '/cancel')) ?>" data-confirm="Отменить бронирование №<?= (int) $b['id'] ?>?<?= $b['pay_status'] === 'paid' ? ' Оплата будет возвращена.' : '' ?>">
                                    <?= csrf_field() ?><button class="btn btn-outline-danger btn-sm">Отменить</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($bookings === []): ?><tr><td colspan="7" class="text-center text-muted py-4">Ничего не найдено</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<div class="mt-4"><?= $view->partial('pagination', ['paginator' => $paginator, 'request' => $request]) ?></div>
