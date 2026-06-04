<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database;
use PDO;

final class PrivilegeRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function hasPrivilege(int $roleId, string $privilege): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*)
            FROM role_privileges rp
            JOIN privileges p ON p.id = rp.privilege_id
            WHERE rp.role_id = ?
                AND p.name = ?
        ");
        $stmt->execute([$roleId, $privilege]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
