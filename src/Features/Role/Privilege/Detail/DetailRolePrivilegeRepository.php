<?php

declare(strict_types=1);

namespace App\Features\Role\Privilege\Detail;

use App\Database;
use PDO;

final class DetailRolePrivilegeRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function getRoleById(int $roleId): array|false
    {
        $stmt = $this->pdo->prepare("
            SELECT id, name, created_at, updated_at
            FROM roles
            WHERE id = ?
        ");
        $stmt->execute([$roleId]);
        return $stmt->fetch();
    }

    public function getRolePrivilegeNames(int $roleId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT p.name
            FROM role_privileges rp
            JOIN privileges p ON p.id = rp.privilege_id
            WHERE rp.role_id = ?
            ORDER BY p.name ASC
        ");
        $stmt->execute([$roleId]);
        return array_column($stmt->fetchAll(), 'name');
    }
}
