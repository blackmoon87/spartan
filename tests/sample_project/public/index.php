<?php

declare(strict_types=1);

// Error reporting
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Load Autoloader
$autoloadPath = dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
} else {
    // Fallback PSR-4 autoloader for App\ namespace
    spl_autoload_register(function (string $class): void {
        $prefix = 'App\\';
        $baseDir = dirname(dirname(dirname(__DIR__))) . '/src/';
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) === 0) {
            $relativeClass = substr($class, $len);
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) {
                require_once $file;
            }
        }
    });
}

// Register PSR-4 Autoloader for Tests\Sample\ namespace
spl_autoload_register(function (string $class): void {
    $prefix = 'Tests\\Sample\\';
    $baseDir = dirname(__DIR__) . '/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Core\Application;

// Load config array
$config = require_once dirname(dirname(dirname(__DIR__))) . '/config/config.php';

// If database is not configured in .env, default to a self-contained SQLite file
if (empty($config['db']['database'])) {
    $config['db'] = [
        'connection' => 'sqlite',
        'database'   => dirname(__DIR__) . '/database.sqlite',
    ];
}

// Instantiate App
$app = new Application($config);

// Override views path to point to sample project views
$app->view->setViewsPath(dirname(__DIR__) . '/Views');

// Auto-migration check: Create sample tables if they do not exist
if ($app->db !== null) {
    try {
        $driver = $app->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $app->db->exec("CREATE TABLE IF NOT EXISTS test_users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT UNIQUE NOT NULL,
                created_at TEXT NULL,
                updated_at TEXT NULL
            );");
            $app->db->exec("CREATE TABLE IF NOT EXISTS test_orders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                status TEXT NOT NULL,
                total REAL NOT NULL,
                created_at TEXT NULL,
                updated_at TEXT NULL,
                FOREIGN KEY(user_id) REFERENCES test_users(id)
            );");

            // Dental clinic tables (SQLite)
            $app->db->exec("CREATE TABLE IF NOT EXISTS clinic_patients (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                phone TEXT NOT NULL,
                email TEXT UNIQUE NOT NULL,
                medical_history TEXT NULL,
                created_at TEXT NULL,
                updated_at TEXT NULL
            );");
            $app->db->exec("CREATE TABLE IF NOT EXISTS clinic_appointments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                patient_id INTEGER NOT NULL,
                appointment_date TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT 'scheduled',
                treatment_notes TEXT NULL,
                created_at TEXT NULL,
                updated_at TEXT NULL,
                FOREIGN KEY(patient_id) REFERENCES clinic_patients(id) ON DELETE CASCADE
            );");
            $app->db->exec("CREATE TABLE IF NOT EXISTS clinic_invoices (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                patient_id INTEGER NOT NULL,
                appointment_id INTEGER NOT NULL,
                total_amount REAL NOT NULL,
                paid_amount REAL NOT NULL DEFAULT 0.0,
                status TEXT NOT NULL DEFAULT 'unpaid',
                created_at TEXT NULL,
                updated_at TEXT NULL,
                FOREIGN KEY(patient_id) REFERENCES clinic_patients(id) ON DELETE CASCADE,
                FOREIGN KEY(appointment_id) REFERENCES clinic_appointments(id) ON DELETE CASCADE
            );");
        } else {
            // MySQL
            $app->db->exec("CREATE TABLE IF NOT EXISTS `test_users` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(255) NOT NULL,
                `email` VARCHAR(255) UNIQUE NOT NULL,
                `created_at` DATETIME NULL,
                `updated_at` DATETIME NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            $app->db->exec("CREATE TABLE IF NOT EXISTS `test_orders` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `user_id` INT UNSIGNED NOT NULL,
                `status` VARCHAR(255) NOT NULL,
                `total` DECIMAL(10,2) NOT NULL,
                `created_at` DATETIME NULL,
                `updated_at` DATETIME NULL
                -- FOREIGN KEY(user_id) REFERENCES test_users(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            // Dental clinic tables (MySQL)
            $app->db->exec("CREATE TABLE IF NOT EXISTS clinic_patients (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                phone VARCHAR(255) NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                medical_history TEXT NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            $app->db->exec("CREATE TABLE IF NOT EXISTS clinic_appointments (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                patient_id INT UNSIGNED NOT NULL,
                appointment_date DATETIME NOT NULL,
                status VARCHAR(50) NOT NULL DEFAULT 'scheduled',
                treatment_notes TEXT NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                FOREIGN KEY(patient_id) REFERENCES clinic_patients(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            $app->db->exec("CREATE TABLE IF NOT EXISTS clinic_invoices (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                patient_id INT UNSIGNED NOT NULL,
                appointment_id INT UNSIGNED NOT NULL,
                total_amount DECIMAL(10,2) NOT NULL,
                paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                status VARCHAR(50) NOT NULL DEFAULT 'unpaid',
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                FOREIGN KEY(patient_id) REFERENCES clinic_patients(id) ON DELETE CASCADE,
                FOREIGN KEY(appointment_id) REFERENCES clinic_appointments(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        }
        
        // Seed default user if empty
        $count = $app->db->query("SELECT COUNT(*) FROM test_users")->fetchColumn();
        if ((int)$count === 0) {
            $stmt = $app->db->prepare("INSERT INTO test_users (name, email, created_at, updated_at) VALUES (?, ?, ?, ?)");
            $now = date('Y-m-d H:i:s');
            $stmt->execute(['Sample Customer', 'customer@mail.com', $now, $now]);
        }
    } catch (\Throwable $e) {
        error_log("Sample migration failed: " . $e->getMessage());
    }
}

// Boot up routes for sample project
require_once dirname(__DIR__) . '/routes/web.php';

// Run Application
$app->run();
