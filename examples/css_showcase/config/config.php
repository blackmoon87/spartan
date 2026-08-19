<?php

declare(strict_types=1);

return [
    'app' => [
        'name'  => 'Spartan CSS Multi-Support Showcase',
        'env'   => 'local',
        'debug' => true,
        'url'   => 'http://localhost:8088',
        'trusted_proxies' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) ($_ENV['TRUSTED_PROXIES'] ?? ''))
        ), fn(string $p): bool => $p !== '')),
    ],
    'views' => [
        'cache_enabled' => false,
        'cache_path'    => __DIR__ . '/../storage/views',
    ],
    'cache' => [
        'driver' => 'file',
        'path'   => __DIR__ . '/../storage/cache',
    ],
];
