<?php
declare(strict_types=1);

namespace Hotel\Repositories;

use PDO;

/** Таблица payments: у каждого бронирования ровно одна оплата. */
final class PaymentRepository
{
    public const METHODS = ['card' => 'Банковская карта', 'online' => 'Онлайн-оплата', 'cash' => 'Наличные при заселении'];

    public function __construct(private PDO $pdo)
    {
    }

    public function create(int $bookingId, string $amount, string $method): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO payments (booking_id, amount, paid_at, method, status)
                                     VALUES (:booking, :amount, CURDATE(), :method, 'paid')");
        $stmt->execute(['booking' => $bookingId, 'amount' => $amount, 'method' => $method]);
    }

    public function refund(int $bookingId): void
    {
        $this->pdo->prepare("UPDATE payments SET status = 'refunded' WHERE booking_id = :booking")->execute(['booking' => $bookingId]);
    }
}
