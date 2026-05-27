<?php

declare(strict_types=1);

namespace App\Core;

class Request
{
    /**
     * Get request URI path, stripping query parameters.
     */
    public function getPath(): string
    {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        $position = strpos($path, '?');
        if ($position === false) {
            return $path;
        }
        return substr($path, 0, $position);
    }

    /**
     * Get request HTTP method (GET, POST, etc.)
     */
    public function getMethod(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Check if request is GET
     */
    public function isGet(): bool
    {
        return $this->getMethod() === 'GET';
    }

    /**
     * Check if request is POST
     */
    public function isPost(): bool
    {
        return $this->getMethod() === 'POST';
    }

    /**
     * Retrieve a parameter from the query string ($_GET).
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Retrieve a parameter from the request body ($_POST).
     */
    public function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    /**
     * Retrieve a parameter from either POST or GET inputs.
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    /**
     * Retrieve all inputs for the current request method as a raw associative array.
     *
     * Merge order: GET keys are loaded first, POST keys are loaded second.
     * If the same key exists in both $_GET and $_POST, the POST value wins.
     * Use get() or post() directly if you need to target a specific source.
     */
    public function getBody(): array
    {
        $body = [];
        if ($this->isGet()) {
            foreach ($_GET as $key => $value) {
                $body[$key] = $value;
            }
        }
        if ($this->isPost()) {
            foreach ($_POST as $key => $value) {
                $body[$key] = $value;
            }
        }
        return $body;
    }

    /**
     * Validate the CSRF token for state-changing POST requests.
     * Handles both form-encoded bodies ($_POST) and JSON bodies (php://input).
     */
    public function validateCsrf(): bool
    {
        if (!$this->isPost()) {
            return true;
        }

        // 1. Check standard header sent by AJAX/fetch clients
        $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if ($headerToken !== null) {
            $sessionToken = Application::$app->session->get('_csrf_token');
            return hash_equals((string) $sessionToken, (string) $headerToken);
        }

        // 2. Check form-encoded POST body
        if (isset($_POST['_csrf'])) {
            $sessionToken = Application::$app->session->get('_csrf_token');
            return hash_equals((string) $sessionToken, (string) $_POST['_csrf']);
        }

        // 3. Check JSON body (Content-Type: application/json)
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $raw  = file_get_contents('php://input');
            $json = json_decode($raw ?: '', true);
            $jsonToken = $json['_csrf'] ?? null;
            if ($jsonToken !== null) {
                $sessionToken = Application::$app->session->get('_csrf_token');
                return hash_equals((string) $sessionToken, (string) $jsonToken);
            }
        }

        // No token found by any method
        return false;
    }

    /**
     * Determine if the request is an AJAX call.
     */
    public function isAjax(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Get the client's IP address.
     */
    public function getIp(): string
    {
        return $_SERVER['HTTP_CLIENT_IP'] 
            ?? $_SERVER['HTTP_X_FORWARDED_FOR'] 
            ?? $_SERVER['REMOTE_ADDR'] 
            ?? '127.0.0.1';
    }
}
