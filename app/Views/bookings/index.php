<?php
/**
 * @var array $bookings
 * @var \Hotel\Core\Paginator $paginator
 * @var string $today
 */
require_once __DIR__ . '/../partials/badges.php';
$methods = \Hotel\Repositories\PaymentRepository::METHODS;
?>
<h1 class="h3 mb-3">Мои бронирования</h1>

<?php if ($bookings === []): ?>
    <div class="alert alert-secondary">У вас пока нет бронирований. <a href="<?= e(url('rooms')) ?>">Выберите номер</a>.</div>
<?php else: ?>
    <div class="table-responsive card shadow-sm">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>№</th><th>Номер</th><th>Заезд</th><th>Выезд</th><th>Гостей</th><th class="text-end">Оплата</th><th>Статус</th><th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $b): ?>
                    <tr>
                        <td><?= (int) $b['id'] ?></td>
                        <td><a href="<?= e(url('rooms/' . $b['room_id'])) ?>"><?= e($b['room_number']) ?></a> <?= class_badge($b['room_class']) ?></td>
                        <td><?= e(date_ru($b['date_from'])) ?></td>
                        <td><?= e(date_ru($b['date_to'])) ?></td>
                        <td><?= (int) $b['guests'] ?></td>
                        <td class="text-end">
                            <?= e(money($b['amount'] ?? 0)) ?><br>
                            <span class="small text-muted"><?= e($methods[$b['pay_method']] ?? '') ?><?= $b['pay_status'] === 'refunded' ? ', возвращена' : '' ?></span>
                        </td>
                        <td><?= status_badge($b, $today) ?></td>
                        <td class="text-end">
                            <?php if ($b['status'] === 'confirmed' && $b['date_from'] > $today): ?>
                                <form method="post" action="<?= e(url('bookings/' . $b['id'] . '/cancel')) ?>" data-confirm="Отменить бронирование №<?= (int) $b['id'] ?>?">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-outline-danger btn-sm">Отменить</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="mt-4"><?= $view->partial('pagination', ['paginator' => $paginator, 'request' => $request]) ?></div>
<?php endif; ?>
