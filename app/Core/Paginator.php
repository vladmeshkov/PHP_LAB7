<?php
declare(strict_types=1);

namespace Hotel\Core;

/** Постраничный вывод: считает смещение для SQL и страницы для навигации. */
final class Paginator
{
    public readonly int $pages;
    public readonly int $page;

    public function __construct(public readonly int $total, public readonly int $perPage, int $page)
    {
        $this->pages = max(1, (int) ceil($total / $perPage));
        $this->page = min(max(1, $page), $this->pages);
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }
}
