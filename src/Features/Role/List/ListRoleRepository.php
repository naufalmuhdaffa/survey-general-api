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
            SELECT id, name, created_at, updated_at
            FROM roles
            WHERE name <> 'user'
            ORDER BY name ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
