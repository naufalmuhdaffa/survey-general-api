<?php

declare(strict_types=1);

namespace App;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $instance = null;

    public static function connection(): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $host = $_ENV['DB_HOST'] ?? throw new RuntimeException('DB_HOST tidak ditemukan di .env');
        $port = $_ENV['DB_PORT'] ?? throw new RuntimeException('DB_PORT tidak ditemukan di .env');
        $name = $_ENV['DB_NAME'] ?? throw new RuntimeException('DB_NAME tidak ditemukan di .env');
        $username = $_ENV['DB_USER'] ?? throw new RuntimeException('DB_USER tidak ditemukan di .env');
        $password = $_ENV['DB_PASS'] ?? throw new RuntimeException('DB_PASS tidak ditemukan di .env');

        $dsn = "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4";

        try {
            self::$instance = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            throw new RuntimeException('Koneksi database gagal: ' . $e->getMessage());
        }

        return self::$instance;
    }
}