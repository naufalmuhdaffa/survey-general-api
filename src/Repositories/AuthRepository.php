<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database;
use PDO;

final class AuthRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function findById(int $userId): array|false
    {
        $stmt = $this->pdo->prepare("
            SELECT id, username, role, position, is_active
            FROM users
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
}
