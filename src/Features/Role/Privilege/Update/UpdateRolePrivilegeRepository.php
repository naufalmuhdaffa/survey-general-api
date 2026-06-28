<?php

declare(strict_types=1);

namespace App\Features\Role\Privilege\Update;

use App\Database;
use PDO;
use Throwable;

final class UpdateRolePrivilegeRepository
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

    public function getUnknownPrivilegeNames(array $privileges): array
    {
        if (empty($privileges)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, \count($privileges), '?'));
        $stmt = $this->pdo->prepare("
            SELECT name
            FROM privileges
            WHERE name IN ($placeholders)
        ");
        $stmt->execute($privileges);

        $knownPrivileges = array_column($stmt->fetchAll(), 'name');
        return array_values(array_diff($privileges, $knownPrivileges));
    }

    public function replaceRolePrivileges(int $roleId, array $privileges): void
    {
        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare("DELETE FROM role_privileges WHERE role_id = ?");
            $stmt->execute([$roleId]);

            if (!empty($privileges)) {
                $placeholders = implode(',', array_fill(0, \count($privileges), '?'));
                $stmt = $this->pdo->prepare("
                    SELECT id
                    FROM privileges
                    WHERE name IN ($placeholders)
                ");
                $stmt->execute($privileges);
                $privilegeIds = array_column($stmt->fetchAll(), 'id');

                $insertPlaceholders = implode(', ', array_fill(0, \count($privilegeIds), '(?, ?)'));
                $values = [];

                foreach ($privilegeIds as $privilegeId) {
                    $values[] = $roleId;
                    $values[] = (int) $privilegeId;
                }

                $stmt = $this->pdo->prepare("
                    INSERT INTO role_privileges (role_id, privilege_id)
                    VALUES $insertPlaceholders
                ");
                $stmt->execute($values);
            }

            $stmt = $this->pdo->prepare("UPDATE roles SET updated_at = NOW() WHERE id = ?");
            $stmt->execute([$roleId]);

            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
