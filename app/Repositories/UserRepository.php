<?php
declare(strict_types=1);

namespace Hotel\Repositories;

use PDO;

/** Доступ к таблице users. Все значения передаются в запросы только параметрами. */
final class UserRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute(['email' => mb_strtolower($email)]);

        return $stmt->fetch() ?: null;
    }

    public function create(string $name, string $email, string $passwordHash, string $role = 'user'): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :hash, :role)');
        $stmt->execute(['name' => $name, 'email' => mb_strtolower($email), 'hash' => $passwordHash, 'role' => $role]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updatePassword(int $id, string $hash): void
    {
        $this->pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id')->execute(['hash' => $hash, 'id' => $id]);
    }

    public function updateRole(int $id, string $role): void
    {
        $this->pdo->prepare('UPDATE users SET role = :role WHERE id = :id')->execute(['role' => $role, 'id' => $id]);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare('DELETE FROM users WHERE id = :id')->execute(['id' => $id]);
    }

    public function count(string $search = ''): int
    {
        [$where, $params] = $this->searchClause($search);
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM users u ' . $where);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /** Список пользователей с числом броней; поиск по имени или email. */
    public function page(string $search, int $limit, int $offset): array
    {
        [$where, $params] = $this->searchClause($search);
        $sql = 'SELECT u.id, u.name, u.email, u.role, u.created_at,
                       (SELECT COUNT(*) FROM bookings b WHERE b.user_id = u.id) AS bookings_count
                FROM users u ' . $where . ' ORDER BY u.id LIMIT :limit OFFSET :offset';
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function countAdmins(): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    }

    /** @return array{0: string, 1: array<string, string>} */
    private function searchClause(string $search): array
    {
        if ($search === '') {
            return ['', []];
        }

        // Спецсимволы LIKE (%, _) в поисковой строке экранируются, чтобы искать их как обычные символы.
        $like = '%' . addcslashes($search, '%_\\') . '%';

        return ['WHERE (u.name LIKE :q1 OR u.email LIKE :q2)', ['q1' => $like, 'q2' => $like]];
    }
}
