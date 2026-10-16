<?php
/**
 * @var array $bookings
 * @var \Hotel\Core\Paginator $paginator
 * @var string $today
 */
require_once __DIR__ . '/../partials/badges.php';
$methods = \Hotel\Repositories\PaymentRepository::METHODS;
?>
<div class="page-head"><h1>Мои бронирования</h1><p>История проживаний и предстоящие заезды.</p></div>

<?php if ($bookings === []): ?>
    <div class="card card-body text-center py-5"><h3>Бронирований пока нет</h3><p class="text-muted mb-3">Выберите номер и даты, и бронь появится здесь.</p><div><a class="btn btn-primary" href="<?= e(url('rooms')) ?>">Подобрать номер</a></div></div>
<?php else: ?>
    <div class="table-responsive card table-card">
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
