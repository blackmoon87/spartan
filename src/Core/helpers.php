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

if (!function_exists('auth')) {
    function auth(): \App\Core\AuthInterface
    {
        return \App\Core\Application::$app->auth;
    }
}

if (!function_exists('slugify')) {
    function slugify(string $text): string
    {
        // Replace non-letters/digits by -
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        // Remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);
        // Trim hyphens
        $text = trim($text, '-');
        // Remove duplicate hyphens
        $text = preg_replace('~-+~', '-', $text);
        // Lowercase
        $text = mb_strtolower($text, 'UTF-8');

        return empty($text) ? 'n-a' : $text;
    }
}
