<?php /** @var int $status @var string $title @var string $details */ ?>
<div class="text-center py-5">
    <div class="display-1 fw-bold text-muted"><?= (int) $status ?></div>
    <h1 class="h3 mb-3"><?= e($title) ?></h1>
    <?php if ($status === 419): ?>
        <p class="text-muted">Срок действия формы истёк или запрос выполнен с другого сайта. Вернитесь назад, обновите страницу и повторите действие.</p>
    <?php elseif ($status === 403): ?>
        <p class="text-muted">У вас нет прав для просмотра этой страницы.</p>
    <?php elseif ($status === 404): ?>
        <p class="text-muted">Такой страницы нет или она была удалена.</p>
    <?php elseif ($status === 500): ?>
        <p class="text-muted">Что-то пошло не так. Мы уже записали ошибку, попробуйте позже.</p>
    <?php endif; ?>
    <?php if ($details !== ''): ?><pre class="text-start small bg-light border rounded p-3 d-inline-block"><?= e($details) ?></pre><?php endif; ?>
    <div><a class="btn btn-primary" href="<?= e(url('rooms')) ?>">К списку номеров</a></div>
</div>
