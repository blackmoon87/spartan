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
$debugMode = getenv('APP_DEBUG') === 'true' || ($_ENV['APP_DEBUG'] ?? 'false') === 'true' || true;
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
    spl_autoload_register(function (string $class): void {
        $prefix = 'App\\';
        $baseDir = dirname(__DIR__) . '/src/';
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) return;
        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) require_once $file;
    });
}

use App\Core\Application;

// Load config
$config = require_once dirname(__DIR__) . '/config/config.php';

// Boot Application
$app = new Application($config);

// ─── Register Middleware ──────────────────────────────────────────────────────
$app->router->aliasMiddleware('auth', \App\Middlewares\AuthMiddleware::class);
$app->router->aliasMiddleware('csrf', \App\Middlewares\CsrfMiddleware::class);
$app->router->setGlobalMiddlewares([
    \App\Middlewares\CsrfMiddleware::class,
]);

// Share global view variables
$app->view->share('appName', 'TaskForge');

// ─── Load Routes ──────────────────────────────────────────────────────────────
$routesPath = dirname(__DIR__) . '/routes';
require_once $routesPath . '/web.php';
require_once $routesPath . '/api.php';

// Start the Application
$app->run();
