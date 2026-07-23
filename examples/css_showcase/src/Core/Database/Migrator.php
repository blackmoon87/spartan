<?php

declare(strict_types=1);

namespace App\Core\Database;

use PDO;

class Migrator
{
    private PDO $db;
    private string $migrationsPath;

    public function __construct(PDO $db, ?string $migrationsPath = null)
    {
        $this->db = $db;
        $this->migrationsPath = $migrationsPath ?: dirname(dirname(dirname(__DIR__))) . '/database/migrations';
    }

    /**
     * Run all pending migrations.
     */
    public function migrate(): void
    {
        $this->createMigrationsTable();

        if (!is_dir($this->migrationsPath)) {
            mkdir($this->migrationsPath, 0755, true);
        }

        $files = glob($this->migrationsPath . '/*.sql');
        if ($files === false) {
            return;
        }

        sort($files);

        $executed = $this->getExecutedMigrations();

        foreach ($files as $file) {
            $filename = basename($file);
            if (in_array($filename, $executed, true)) {
                continue;
            }

            echo "Migrating: {$filename}...\n";
            $sql = file_get_contents($file);
            
            // Auto-translate dialect based on active driver
            $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'sqlite') {
                // MySQL -> SQLite translation
                $sql = str_ireplace('INT UNSIGNED AUTO_INCREMENT PRIMARY KEY', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
                $sql = str_ireplace('INT AUTO_INCREMENT PRIMARY KEY', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
                $sql = str_ireplace('INT AUTO_INCREMENT', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
                // Universal ENUM → VARCHAR(50) (handles ANY enum values, not just specific ones)
                $sql = preg_replace('/ENUM\s*\([^)]+\)/i', 'VARCHAR(50)', $sql);
                // TINYINT UNSIGNED → INTEGER (must come before generic UNSIGNED removal)
                $sql = preg_replace('/TINYINT\s+UNSIGNED/i', 'INTEGER', $sql);
                // JSON type → TEXT (SQLite has no native JSON column type)
                $sql = preg_replace('/\bJSON\b/i', 'TEXT', $sql);
                // Strip remaining UNSIGNED modifiers
                $sql = preg_replace('/\bUNSIGNED\b/i', '', $sql);
                // Strip ON UPDATE CURRENT_TIMESTAMP (not supported in SQLite)
                $sql = preg_replace('/ON\s+UPDATE\s+CURRENT_TIMESTAMP/i', '', $sql);
                $sql = preg_replace('/ENGINE\s*=\s*\w+/i', '', $sql);
                $sql = preg_replace('/DEFAULT\s+CHARSET\s*=\s*\w+/i', '', $sql);
                $sql = preg_replace('/COLLATE\s*=\s*\w+/i', '', $sql);
                $sql = preg_replace('/COMMENT\s+\'[^\']+\'/i', '', $sql);
            } else {
                // SQLite -> MySQL translation
                $sql = str_ireplace('INTEGER PRIMARY KEY AUTOINCREMENT', 'INT AUTO_INCREMENT PRIMARY KEY', $sql);
            }

            // Execute SQL (DDL statements trigger implicit commits in SQLite/MySQL, so do not run in an explicit transaction)
            try {
                // Split multi-statement SQL files for drivers that don't support multi-query via exec (like SQLite/MySQL sometimes)
                $queries = array_filter(array_map('trim', explode(';', $sql)));
                foreach ($queries as $query) {
                    if ($query !== '') {
                        $this->db->exec($query);
                    }
                }
                $this->logMigration($filename);
                echo "Migrated:  {$filename}\n";
            } catch (\Throwable $e) {
                throw $e;
            }
        }
    }

    /**
     * Create the migrations tracking table if not exists.
     */
    private function createMigrationsTable(): void
    {
        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $sql = "CREATE TABLE IF NOT EXISTS `migrations` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `migration` VARCHAR(255) NOT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            )";
        } else {
            $sql = "CREATE TABLE IF NOT EXISTS `migrations` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `migration` VARCHAR(255) NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        }

        $this->db->exec($sql);
    }

    /**
     * Get list of already executed migration filenames.
     */
    private function getExecutedMigrations(): array
    {
        $stmt = $this->db->query("SELECT `migration` FROM `migrations` ORDER BY `id` ASC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    /**
     * Log an executed migration file.
     */
    private function logMigration(string $filename): void
    {
        $stmt = $this->db->prepare("INSERT INTO `migrations` (`migration`) VALUES (?)");
        $stmt->execute([$filename]);
    }
}
