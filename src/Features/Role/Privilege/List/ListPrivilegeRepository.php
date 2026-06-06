<?php

declare(strict_types=1);

namespace App\Features\Role\Privilege\List;

use App\Database;
use PDO;

final class ListPrivilegeRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function getPrivileges(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT name, created_at, updated_at
            FROM privileges
            ORDER BY name ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
