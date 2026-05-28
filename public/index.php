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

// Error reporting — controlled strictly by APP_DEBUG env flag.
// NEVER enable display_errors in production. This block reads the .env
// value before the Application boots so errors during boot are visible locally.
$debugMode = getenv('APP_DEBUG') === 'true' || ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
if ($debugMode) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
}

// Load Autoloader
$autoloadPath = dirname(__DIR__) . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
} else {
    // Fallback PSR-4 autoloader for plug-and-play setup without requiring composer immediately
    spl_autoload_register(function (string $class): void {
        $prefix = 'App\\';
        $baseDir = dirname(__DIR__) . '/src/';
        
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
}

use App\Core\Application;

// Load config array
$config = require_once dirname(__DIR__) . '/config/config.php';

// Instantiate App
$app = new Application($config);

// ─── Load Routes ──────────────────────────────────────────────────────────────
// If route cache is enabled and not in debug mode, load from cache.
$routesPath = dirname(__DIR__) . '/routes';
$routeCacheEnabled = $config['router']['cache_enabled'] ?? false;

if ($routeCacheEnabled && !$debugMode && $app->router->loadCache()) {
    // Loaded from cache
} else {
    require_once $routesPath . '/web.php';
    require_once $routesPath . '/admin.php';
    require_once $routesPath . '/api.php';

    if ($routeCacheEnabled && !$debugMode) {
        $app->router->saveCache();
    }
}

// Start the Application
$app->run();
