<?php

declare(strict_types=1);

// Support PHP built-in web server static files
if (php_sapi_name() === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $file = __DIR__ . $path;
    if (is_file($file)) {
        return false;
    }
}

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

// Register Policies
\App\Core\Gate::policy(\Tests\Sample\Models\Post::class, \Tests\Sample\Policies\PostPolicy::class);

// Resolve and bind auth_user in container
$userId = $app->session->get('user_id');
if ($userId) {
    try {
        $user = (new \Tests\Sample\Models\User())->findInstance($userId);
        if ($user) {
            $app->container->instance('auth_user', $user);
            $app->view->share('authUser', $user);
        }
    } catch (\Throwable $e) {
        // DB not ready yet during initial migration
    }
}

// Register Event Listeners
$app->events->listen(
    \Tests\Sample\Events\CommentPostedEvent::class,
    \Tests\Sample\Listeners\NotifyPostOwner::class,
    async: true
);

// Auto-migration check: Run pending migrations if not exists
if ($app->db !== null) {
    try {
        $migrator = new \App\Core\Database\Migrator($app->db);
        $migrator->migrate();

        // Seed default roles, permissions, and users if users table is empty
        $stmt = $app->db->query("SELECT COUNT(*) FROM `users`");
        $userCount = (int)($stmt->fetchColumn() ?: 0);
        if ($userCount === 0) {
            // Seed default roles & permissions
            $seedFile = dirname(dirname(dirname(__DIR__))) . '/database/seed.sql';
            if (file_exists($seedFile)) {
                $seedSql = file_get_contents($seedFile);
                $driver = $app->db->getAttribute(PDO::ATTR_DRIVER_NAME);
                if ($driver === 'sqlite') {
                    $seedSql = preg_replace('/ON DUPLICATE KEY UPDATE[^;]+/i', '', $seedSql);
                    $seedSql = str_ireplace('INSERT INTO', 'INSERT OR IGNORE INTO', $seedSql);
                }
                $queries = array_filter(array_map('trim', explode(';', $seedSql)));
                foreach ($queries as $query) {
                    if ($query !== '') {
                        $app->db->exec($query);
                    }
                }
            }

            // Seed users & assign roles
            $hashedPassword = password_hash('password123', PASSWORD_BCRYPT);
            $now = date('Y-m-d H:i:s');

            $stmtUser = $app->db->prepare("INSERT INTO users (name, email, password, created_at, updated_at) VALUES (?, ?, ?, ?, ?)");
            
            // System Admin User
            $stmtUser->execute(['System Admin', 'admin@mail.com', $hashedPassword, $now, $now]);
            $adminId = $app->db->lastInsertId();

            // Sample Author User
            $stmtUser->execute(['Sample Author', 'author@mail.com', $hashedPassword, $now, $now]);
            $authorId = $app->db->lastInsertId();

            if ($adminId && $authorId) {
                // Fetch roles
                $adminRole = $app->db->query("SELECT id FROM roles WHERE slug = 'admin'")->fetchColumn();
                $userRole = $app->db->query("SELECT id FROM roles WHERE slug = 'user'")->fetchColumn();

                if ($adminRole) {
                    $app->db->exec("INSERT INTO user_roles (user_id, role_id) VALUES ({$adminId}, {$adminRole})");
                }
                if ($userRole) {
                    $app->db->exec("INSERT INTO user_roles (user_id, role_id) VALUES ({$authorId}, {$userRole})");
                }

                // Seed first welcome post
                $stmtPost = $app->db->prepare("INSERT INTO posts (user_id, title, body, created_at, updated_at) VALUES (?, ?, ?, ?, ?)");
                $stmtPost->execute([(int)$authorId, 'Welcome to Spartan Blogger', 'This is the very first blog post on this amazing Spartan framework.', $now, $now]);
            }
        }
    } catch (\Throwable $e) {
        error_log("Sample migration failed: " . $e->getMessage());
    }
}

// Boot up routes for sample project
require_once dirname(__DIR__) . '/routes/web.php';

// Run Application
$app->run();
