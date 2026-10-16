<?php
declare(strict_types=1);

namespace Hotel\Core;

use RuntimeException;

/** Ошибка с HTTP-кодом: 403, 404, 419 и т. д. Превращается в страницу ошибки. */
final class HttpException extends RuntimeException
{
    public function __construct(private int $status, string $message = '')
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->status;
    }
}
