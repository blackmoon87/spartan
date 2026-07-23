<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    /**
     * Singleton constructor: prevents direct instantiation.
     */
    private function __construct() {}

    /**
     * Get the PDO database connection instance.
     */
    public static function getInstance(?array $config = null): PDO
    {
        if (self::$instance === null) {
            if ($config === null) {
                $config = \App\Core\Application::$app ? \App\Core\Application::$app->config['db'] : [];
            }
            $connection = $config['connection'] ?? 'mysql';

            if ($connection === 'sqlite') {
                $dbPath = $config['database'] ?? ':memory:';
                if ($dbPath !== ':memory:' && !str_starts_with($dbPath, '/') && !preg_match('#^[a-zA-Z]:\\\\#', $dbPath)) {
                    $dbPath = dirname(dirname(__DIR__)) . '/' . $dbPath;
                }
                $dsn = 'sqlite:' . $dbPath;
            } else {
                $dsn = sprintf(
                    '%s:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                    $connection,
                    $config['host'] ?? '127.0.0.1',
                    $config['port'] ?? '3306',
                    $config['database'] ?? ''
                );
            }

            try {
                self::$instance = new PDO($dsn, $config['username'] ?? '', $config['password'] ?? '', [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                throw new PDOException("Database connection failed: " . $e->getMessage(), (int)$e->getCode());
            }
        }

        return self::$instance;
    }

    /**
     * Reset the singleton instance.
     * Use ONLY in testing environments to get a fresh connection.
     */
    public static function reset(): void
    {
        self::$instance = null;
    }

    /**
     * Swap the internal PDO instance with a mock or test double.
     * Use ONLY in testing environments.
     */
    public static function swapInstance(PDO $mock): void
    {
        self::$instance = $mock;
    }
}
