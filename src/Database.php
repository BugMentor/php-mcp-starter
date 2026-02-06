<?php

declare(strict_types=1);

namespace BugMentor\Mcp;

use PDO;

final class Database
{
    public static function createFromEnv(): PDO
    {
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $port = getenv('DB_PORT') ?: '5432';
        $name = getenv('DB_NAME') ?: 'mcp_sales';
        $user = getenv('DB_USER') ?: 'mcp';
        $pass = getenv('DB_PASSWORD') ?: 'mcp_secret';

        $dsn = "pgsql:host={$host};port={$port};dbname={$name}";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    }
}
