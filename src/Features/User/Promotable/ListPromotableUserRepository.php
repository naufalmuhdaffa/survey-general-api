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
            SELECT
                u.id,
                u.nik,
                u.full_name,
                u.username,
                u.role_id,
                r.name AS role,
                u.position,
                u.is_active,
                u.created_at,
                u.updated_at
            FROM users u
            JOIN roles r ON r.id = u.role_id
            WHERE r.name = 'user'
            ORDER BY u.full_name ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
