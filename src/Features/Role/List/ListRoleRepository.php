<?php

declare(strict_types=1);

namespace App\Features\Role\List;

use App\Database;
use PDO;

final class ListRoleRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function getRoles(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                r.id,
                r.name,
                r.created_at,
                r.updated_at,
                COUNT(u.id) AS user_count,
                COALESCE(SUM(CASE WHEN u.is_active = 1 THEN 1 ELSE 0 END), 0) AS active_user_count
            FROM roles r
            LEFT JOIN users u ON u.role_id = r.id
            WHERE r.name <> 'user'
            GROUP BY r.id, r.name, r.created_at, r.updated_at
            ORDER BY r.name ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
