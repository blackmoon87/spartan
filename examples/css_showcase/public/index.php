<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Core/helpers.php';

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../src/';
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

$config = require __DIR__ . '/../config/config.php';
$app = new App\Core\Application($config);

require_once __DIR__ . '/../routes/web.php';

$app->run();
