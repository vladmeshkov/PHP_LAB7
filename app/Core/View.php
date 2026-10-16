<?php
declare(strict_types=1);

namespace Hotel\Core;

use RuntimeException;

/** Подключает шаблоны из app/Views и вставляет их в общий макет. */
final class View
{
    public function __construct(private string $path)
    {
    }

    public function render(string $template, array $data = [], ?string $layout = 'layouts/main'): string
    {
        // Помощники для шаблонов (метки, иконки, картинки) доступны на всех страницах
        require_once $this->path . '/partials/badges.php';

        $content = $this->include($template, $data);

        return $layout === null ? $content : $this->include($layout, $data + ['content' => $content]);
    }

    private function include(string $template, array $data): string
    {
        $file = $this->path . '/' . $template . '.php';
        if (!is_file($file)) {
            throw new RuntimeException("Шаблон {$template} не найден");
        }

        // Данные доступны в шаблоне как обычные переменные; сам шаблон ничего не меняет в приложении.
        return (static function (string $__file, array $__data): string {
            extract($__data, EXTR_SKIP);
            ob_start();
            require $__file;

            return (string) ob_get_clean();
        })($file, $data);
    }

    public function partial(string $template, array $data = []): string
    {
        return $this->include('partials/' . $template, $data);
    }
}
