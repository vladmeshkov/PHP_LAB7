<?php
declare(strict_types=1);

namespace Hotel\Repositories;

use PDO;

/** Таблица payments: у каждого бронирования ровно одна оплата со своим статусом. */
final class PaymentRepository
{
    public const METHODS = ['card' => 'Банковская карта', 'online' => 'Онлайн-оплата', 'cash' => 'При заселении'];

    public function __construct(private PDO $pdo)
    {
    }

    /** Оплата создаётся вместе с бронью в статусе «ожидает». Для карты и онлайн-оплаты задаётся срок. */
    public function create(int $bookingId, string $amount, string $method, ?string $dueAt): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO payments (booking_id, amount, method, status, due_at)
                                     VALUES (:booking, :amount, :method, 'pending', :due)");
        $stmt->execute(['booking' => $bookingId, 'amount' => $amount, 'method' => $method, 'due' => $dueAt]);
    }

    public function markPaid(int $bookingId): void
    {
        $this->pdo->prepare("UPDATE payments SET status = 'paid', paid_at = NOW(), due_at = NULL WHERE booking_id = :booking AND status = 'pending'")
            ->execute(['booking' => $bookingId]);
    }

    /** Оплаченное возвращается, неоплаченное просто закрывается. */
    public function close(int $bookingId): void
    {
        $this->pdo->prepare("UPDATE payments SET status = CASE status WHEN 'paid' THEN 'refunded' ELSE 'cancelled' END, due_at = NULL
                             WHERE booking_id = :booking AND status IN ('paid', 'pending')")
            ->execute(['booking' => $bookingId]);
    }
}
