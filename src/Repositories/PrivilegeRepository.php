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

    public function getPrivilegeNamesByRoleId(int $roleId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT p.name
            FROM role_privileges rp
            JOIN privileges p ON p.id = rp.privilege_id
            WHERE rp.role_id = ?
            ORDER BY p.name ASC
        ");
        $stmt->execute([$roleId]);

        return array_values(array_filter(array_map(
            static fn (array $privilege): string => (string) $privilege['name'],
            $stmt->fetchAll()
        )));
    }
}
