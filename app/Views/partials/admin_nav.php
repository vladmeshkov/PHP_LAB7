<?php
/** Вкладки админ-панели. @var \Hotel\Core\Request $request */
$tabs = ['/admin' => 'Обзор', '/admin/rooms' => 'Номера', '/admin/bookings' => 'Бронирования', '/admin/users' => 'Пользователи'];
$path = $request->path();
?>
<ul class="nav nav-tabs mb-4">
    <?php foreach ($tabs as $href => $label): ?>
        <?php $isActive = $href === '/admin' ? $path === '/admin' : str_starts_with($path, $href); ?>
        <li class="nav-item"><a class="nav-link<?= $isActive ? ' active' : '' ?>" href="<?= e(url($href)) ?>"><?= e($label) ?></a></li>
    <?php endforeach; ?>
</ul>
