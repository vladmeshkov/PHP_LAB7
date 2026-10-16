<?php
/**
 * @var array $bookings
 * @var \Hotel\Core\Paginator $paginator
 */
use Hotel\Services\BookingStatus;
?>
<div class="page-head"><h1>Мои бронирования</h1><p>Бронь нужно оплатить, чтобы она осталась за вами. Оплаченную можно отменить до дня заезда.</p></div>

<?php if ($bookings === []): ?>
    <div class="card card-body text-center py-4">
        <h3>Бронирований пока нет</h3>
        <p class="text-muted mb-3">Выберите номер и даты, и бронь появится здесь.</p>
        <div><a class="btn btn-primary" href="<?= e(url('rooms')) ?>">Подобрать номер</a></div>
    </div>
<?php else: ?>
    <div class="table-responsive card table-card">
        <table class="table align-middle">
            <thead>
                <tr><th>№</th><th>Номер</th><th>Даты</th><th>Гостей</th><th class="text-end">Сумма</th><th>Статус</th><th></th></tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $b): ?>
                    <tr>
                        <td><?= (int) $b['id'] ?></td>
                        <td><a href="<?= e(url('rooms/' . $b['room_id'])) ?>"><?= e($b['room_number']) ?></a> <?= class_badge($b['room_class']) ?></td>
                        <td class="text-nowrap"><?= e(date_ru($b['date_from'])) ?> – <?= e(date_ru($b['date_to'])) ?></td>
                        <td><?= (int) $b['guests'] ?></td>
                        <td class="text-end text-nowrap"><?= e(money($b['amount'] ?? 0)) ?></td>
                        <td>
                            <?= status_badge($b) ?>
                            <span class="sub"><?= e(cancel_note($b) ?: payment_note($b)) ?></span>
                        </td>
                        <td>
                            <div class="actions">
                                <?php if ($b['status'] === BookingStatus::BOOKED && $b['pay_method'] !== 'cash'): ?>
                                    <form method="post" action="<?= e(url('bookings/' . $b['id'] . '/pay')) ?>">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-success btn-sm">Оплатить</button>
                                    </form>
                                <?php endif; ?>
                                <?php if (in_array($b['status'], BookingStatus::CANCELLABLE, true) && $b['date_from'] > date('Y-m-d')): ?>
                                    <form method="post" action="<?= e(url('bookings/' . $b['id'] . '/cancel')) ?>" data-confirm="Отменить бронирование №<?= (int) $b['id'] ?>?<?= $b['pay_status'] === 'paid' ? ' Оплата будет возвращена.' : '' ?>">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-outline-danger btn-sm">Отменить</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="mt-4"><?= $view->partial('pagination', ['paginator' => $paginator, 'request' => $request]) ?></div>
<?php endif; ?>
