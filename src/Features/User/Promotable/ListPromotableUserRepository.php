<?php

declare(strict_types=1);

namespace App\Features\User\Promotable;

use App\Database;
use PDO;

final class ListPromotableUserRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function getPromotableUsers(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, nik, full_name, username, role, position, is_active, created_at, updated_at
            FROM users
            WHERE role = 'user'
            ORDER BY full_name ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
