<?php
/**
 * @var array $users
 * @var \Hotel\Core\Paginator $paginator
 * @var string $search
 * @var ?array $user  текущий администратор
 */
?>
<?= $view->partial('admin_nav', ['request' => $request]) ?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h4 mb-0">Пользователи <span class="text-muted fs-6">(<?= (int) $paginator->total ?>)</span></h1>
    <form method="get" class="d-flex gap-2">
        <input class="form-control" name="q" placeholder="Имя или email" value="<?= e($search) ?>">
        <button class="btn btn-outline-primary">Найти</button>
    </form>
</div>

<div class="table-responsive card table-card">
    <table class="table align-middle mb-0">
        <thead class="table-light"><tr><th>ID</th><th>Имя</th><th>Email</th><th>Регистрация</th><th class="text-center">Броней</th><th>Роль</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= (int) $u['id'] ?></td>
                    <td><?= e($u['name']) ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td><?= e(date_ru($u['created_at'])) ?></td>
                    <td class="text-center"><?= (int) $u['bookings_count'] ?></td>
                    <td>
                        <form method="post" action="<?= e(url('admin/users/' . $u['id'] . '/role')) ?>" class="d-flex gap-1">
                            <?= csrf_field() ?>
                            <select class="form-select form-select-sm" name="role" aria-label="Роль">
                                <option value="user"<?= $u['role'] === 'user' ? ' selected' : '' ?>>Клиент</option>
                                <option value="admin"<?= $u['role'] === 'admin' ? ' selected' : '' ?>>Администратор</option>
                            </select>
                            <button class="btn btn-outline-secondary btn-sm">OK</button>
                        </form>
                    </td>
                    <td class="text-end">
                        <?php if ((int) $u['id'] !== (int) $user['id']): ?>
                            <form method="post" action="<?= e(url('admin/users/' . $u['id'] . '/delete')) ?>" data-confirm="Удалить пользователя <?= e($u['name']) ?> и все его бронирования?">
                                <?= csrf_field() ?>
                                <button class="btn btn-outline-danger btn-sm">Удалить</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($users === []): ?><tr><td colspan="7" class="text-center text-muted py-4">Ничего не найдено</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<div class="mt-4"><?= $view->partial('pagination', ['paginator' => $paginator, 'request' => $request]) ?></div>
