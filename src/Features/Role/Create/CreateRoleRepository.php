<?php

declare(strict_types=1);

namespace App\Features\Role\Create;

use App\Database;
use PDO;

final class CreateRoleRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function roleNameExists(string $name): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM roles WHERE name = ?");
        $stmt->execute([$name]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function createRole(string $name): int
    {
        $stmt = $this->pdo->prepare("INSERT INTO roles (name) VALUES (?)");
        $stmt->execute([$name]);
        return (int) $this->pdo->lastInsertId();
    }
}
