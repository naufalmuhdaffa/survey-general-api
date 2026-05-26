<?php

declare(strict_types=1);

namespace App\Features\User\List;

use App\Database;
use PDO;

final class ListUserRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function getManagementUsers(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, nik, full_name, username, role, position, is_active, created_at, updated_at
            FROM users
            WHERE role IN ('superadmin', 'admin_opd')
            ORDER BY
                CASE role
                    WHEN 'superadmin' THEN 1
                    WHEN 'admin_opd' THEN 2
                    ELSE 3
                END,
                full_name ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
