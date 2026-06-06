<?php

declare(strict_types=1);

namespace App\Features\Auth\Login;

use PDO;
use App\Database;

final class LoginRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function getUserByNik(string $nik): array|false
    {
        $stmt = $this->pdo->prepare("
            SELECT u.id, u.username, u.password, u.role_id, r.name AS role, u.position, u.is_active
            FROM users u
            JOIN roles r ON r.id = u.role_id
            WHERE u.nik = ?
        ");
        $stmt->execute([$nik]);
        return $stmt->fetch();
    }

    public function getUserByUsername(string $username): array|false
    {
        $stmt = $this->pdo->prepare("
            SELECT u.id, u.username, u.password, u.role_id, r.name AS role, u.position, u.is_active
            FROM users u
            JOIN roles r ON r.id = u.role_id
            WHERE u.username = ?
        ");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }
}
