<?php

declare(strict_types=1);

if (!function_exists('url')) {
    function url(string $path): string
    {
        $basePath = \App\Core\Application::$app->request->getBasePath();
        return $basePath . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return url($path);
    }
}
