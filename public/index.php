<?php

declare(strict_types=1);

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
// Split routes by domain for maintainability. Add new route files here as the
// project grows — each file receives $app in scope automatically.
$routesPath = dirname(__DIR__) . '/routes';
require_once $routesPath . '/web.php';
require_once $routesPath . '/admin.php';
require_once $routesPath . '/api.php';

// Start the Application
$app->run();
