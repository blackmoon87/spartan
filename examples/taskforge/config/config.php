<?php

declare(strict_types=1);

// ─── TaskForge Configuration ──────────────────────────────────────────────────
// Demonstrates every configurable feature of the Spartan framework.

return [
    'app' => [
        'name'  => 'TaskForge',
        'url'   => 'http://localhost:8087',
        'trusted_proxies' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) ($_ENV['TRUSTED_PROXIES'] ?? ''))
        ), fn(string $p): bool => $p !== '')),
        'debug' => true,
    ],

    'db' => [
        'connection' => 'sqlite',
        'database'   => 'database/taskforge.db',
    ],

    'auth' => [
        'model' => 'App\\Models\\User',
    ],

    'cache' => [
        'driver' => 'file',
        'path'   => __DIR__ . '/../storage/cache',
    ],

    'router' => [
        'cache_enabled' => false,
    ],

    'frankenphp_worker' => false, // Set to true or FRANKENPHP_WORKER=true in .env to enable ultra-fast Worker Mode
];
