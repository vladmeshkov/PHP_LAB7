<?php
declare(strict_types=1);

namespace Hotel\Services;

use DomainException;

/** Нарушено правило бронирования; текст сообщения можно показать пользователю. */
final class BookingException extends DomainException
{
}
