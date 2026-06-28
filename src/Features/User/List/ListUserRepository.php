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
            SELECT
                u.id,
                u.nik,
                u.full_name,
                u.username,
                u.email,
                u.phone,
                u.profile_photo_path,
                u.role_id,
                r.name AS role,
                u.position,
                u.is_active,
                u.created_at,
                u.updated_at
            FROM users u
            JOIN roles r ON r.id = u.role_id
            WHERE r.name <> 'user'
            ORDER BY r.name ASC, u.full_name ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
