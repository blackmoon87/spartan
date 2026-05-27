<?php

declare(strict_types=1);

namespace App\Core;

class Response
{
    /**
     * Set the HTTP response status code.
     */
    public function setStatusCode(int $code): void
    {
        http_response_code($code);
    }

    /**
     * Redirect the client to another URL.
     * Only relative paths or URLs matching the configured app base URL
     * are allowed to prevent Open Redirect attacks.
     */
    public function redirect(string $url): void
    {
        // Block absolute URLs that point to external domains
        if (preg_match('#^https?://#i', $url)) {
            $appUrl = Application::$app->config['app']['url'] ?? '';
            if ($appUrl === '' || !str_starts_with($url, $appUrl)) {
                // Refuse external redirects — fall back to home
                $url = '/';
            }
        }

        header('Location: ' . $url);
        exit;
    }

    /**
     * Send a JSON response.
     */
    public function json(mixed $data, int $statusCode = 200): void
    {
        $this->setStatusCode($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
}
