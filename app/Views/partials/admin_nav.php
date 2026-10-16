<?php
/** Разделы админ-панели. @var \Hotel\Core\Request $request */
$tabs = ['/admin' => 'Обзор', '/admin/rooms' => 'Номера', '/admin/bookings' => 'Бронирования', '/admin/users' => 'Пользователи'];
$path = $request->path();
?>
<nav class="admin-tabs" aria-label="Разделы админ-панели">
    <?php foreach ($tabs as $href => $label): ?>
        <?php $isActive = $href === '/admin' ? $path === '/admin' : str_starts_with($path, $href); ?>
        <a class="<?= $isActive ? 'active' : '' ?>" href="<?= e(url($href)) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
</nav>
