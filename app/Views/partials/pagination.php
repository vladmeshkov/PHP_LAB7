<?php
/**
 * Постраничная навигация. Сохраняет остальные параметры адреса (фильтры, сортировку).
 * @var \Hotel\Core\Paginator $paginator
 * @var \Hotel\Core\Request   $request
 */
if ($paginator->pages <= 1) {
    return;
}
$link = static fn (int $page): string => '?' . http_build_query(array_merge($request->queryAll(), ['page' => $page]));

// Показываем текущую страницу и по две соседние, остальное сворачиваем в «…»
$pages = array_filter(range(1, $paginator->pages), static fn (int $p): bool => $p === 1 || $p === $paginator->pages || abs($p - $paginator->page) <= 2);
$prev = 0;
?>
<nav aria-label="Страницы">
    <ul class="pagination justify-content-center mb-0">
        <li class="page-item<?= $paginator->page <= 1 ? ' disabled' : '' ?>">
            <a class="page-link" href="<?= e($link($paginator->page - 1)) ?>">‹</a>
        </li>
        <?php foreach ($pages as $p): ?>
            <?php if ($p - $prev > 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
            <li class="page-item<?= $p === $paginator->page ? ' active' : '' ?>">
                <a class="page-link" href="<?= e($link($p)) ?>"><?= $p ?></a>
            </li>
            <?php $prev = $p; ?>
        <?php endforeach; ?>
        <li class="page-item<?= $paginator->page >= $paginator->pages ? ' disabled' : '' ?>">
            <a class="page-link" href="<?= e($link($paginator->page + 1)) ?>">›</a>
        </li>
    </ul>
</nav>
