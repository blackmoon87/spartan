<?php

declare(strict_types=1);

namespace App\Examples\CssShowcase;

use App\Core\Application;
use App\Core\Request;
use App\Core\Response;

define('SPARTAN_TESTING', true);

require_once __DIR__ . '/src/Core/helpers.php';

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/src/';
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

$testsPassed = 0;
$testsFailed = 0;

function assertCondition(bool $condition, string $testName, string $details = ''): void
{
    global $testsPassed, $testsFailed;
    if ($condition) {
        $testsPassed++;
        echo "  [PASS] {$testName}" . ($details ? " ({$details})" : "") . "\n";
    } else {
        $testsFailed++;
        echo "  [FAIL] {$testName}" . ($details ? " ({$details})" : "") . "\n";
    }
}

echo "═══════════════════════════════════════════════════════════════════\n";
echo "   SPARTAN CSS MULTI-SUPPORT SHOWCASE TEST SUITE                  \n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

$config = require __DIR__ . '/config/config.php';
$app = new Application($config);

require_once __DIR__ . '/routes/web.php';

// Test 1: Hub Index Route
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/';
$app->router->setRequest(new Request());
$htmlHub = $app->router->resolve();
assertCondition(
    str_contains($htmlHub, 'Multi-CSS Framework Engine Hub') && str_contains($htmlHub, 'Vanilla Glassmorphism'),
    '1. CSS Engine Hub Homepage Rendering',
    'Rendered hub layout with glassmorphic cards'
);

// Test 2: Tailwind CSS Integration Route
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/css/tailwind';
$app->router->setRequest(new Request());
$htmlTailwind = $app->router->resolve();
assertCondition(
    str_contains($htmlTailwind, 'https://cdn.tailwindcss.com') && str_contains($htmlTailwind, 'bg-slate-950') && str_contains($htmlTailwind, 'Utility First'),
    '2. Tailwind CSS Layout & Utility Classes Compilation',
    'CDN script injected, Tailwind classes compiled correctly'
);

// Test 3: Open Props CSS Integration Route
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/css/openprops';
$app->router->setRequest(new Request());
$htmlOpenProps = $app->router->resolve();
assertCondition(
    str_contains($htmlOpenProps, 'https://unpkg.com/open-props') && str_contains($htmlOpenProps, '--surface-1') && str_contains($htmlOpenProps, '--size-1'),
    '3. Open Props Custom Properties Layout Compilation',
    'Open Props CSS variables evaluated inside Blade directives'
);

// Test 4: Vanilla Glassmorphism Engine Route
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/css/vanilla';
$app->router->setRequest(new Request());
$htmlVanilla = $app->router->resolve();
assertCondition(
    str_contains($htmlVanilla, 'backdrop-filter: blur') && str_contains($htmlVanilla, '1.8 ms'),
    '4. Vanilla Glassmorphism CSS Engine Rendering',
    'Rendered custom backdrop filter cards & performance metrics'
);

// Test 5: Asset Helper CSS Path Generation
$assetCssPath = asset('css/app.css');
assertCondition(
    $assetCssPath === '/css/app.css',
    '5. Asset Helper CSS Resolution',
    'Resolved relative path: ' . $assetCssPath
);

echo "\n───────────────────────────────────────────────────────────────────\n";
echo " TEST RESULTS: {$testsPassed} Passed, {$testsFailed} Failed\n";
echo "───────────────────────────────────────────────────────────────────\n\n";

if ($testsFailed > 0) {
    exit(1);
}
