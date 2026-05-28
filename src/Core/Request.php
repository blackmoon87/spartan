<?php

declare(strict_types=1);

namespace App\Core;

class Request
{
    protected ?array $jsonParams = null;

    /**
     * Get request URI path, stripping query parameters and base subdirectory path.
     */
    public function getPath(): string
    {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        $position = strpos($path, '?');
        if ($position !== false) {
            $path = substr($path, 0, $position);
        }

        $basePath = $this->getBasePath();
        if ($basePath !== '') {
            if (strpos($path, $basePath) === 0) {
                $path = substr($path, strlen($basePath));
            }
        }

        return $path === '' ? '/' : $path;
    }

    /**
     * Get base directory path if application is running in a subdirectory.
     */
    public function getBasePath(): string
    {
        if (PHP_SAPI === 'cli') {
            return '';
        }
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $scriptDir = dirname($scriptName);
        if ($scriptDir === '/' || $scriptDir === '\\') {
            return '';
        }
        return str_replace('\\', '/', $scriptDir);
    }

    /**
     * Get request HTTP method (GET, POST, etc.) with support for spoofed methods.
     */
    public function getMethod(): string
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($method === 'POST') {
            $spoofed = $_POST['_method'] ?? $this->getJsonParams()['_method'] ?? null;
            if ($spoofed && in_array(strtoupper($spoofed), ['PUT', 'PATCH', 'DELETE'], true)) {
                return strtoupper($spoofed);
            }
        }
        return $method;
    }

    /**
     * Get the real underlying request HTTP method without method spoofing.
     */
    public function getRealMethod(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Check if the request is secure (HTTPS).
     */
    public function isSecure(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['SERVER_PORT'] ?? 80) == 443)
            || (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
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
        return $this->getRealMethod() === 'POST';
    }

    /**
     * Retrieve a parameter from the query string ($_GET).
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Retrieve a parameter from the request body ($_POST or JSON payload).
     */
    public function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $this->getJsonParams()[$key] ?? $default;
    }

    /**
     * Retrieve a parameter from either POST, JSON payload, or GET inputs.
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $this->getJsonParams()[$key] ?? $_GET[$key] ?? $default;
    }

    /**
     * Retrieve all inputs for the current request method as a raw associative array.
     */
    public function getBody(): array
    {
        $body = [];
        
        foreach ($_GET as $key => $value) {
            $body[$key] = $value;
        }

        foreach ($_POST as $key => $value) {
            $body[$key] = $value;
        }

        foreach ($this->getJsonParams() as $key => $value) {
            $body[$key] = $value;
        }

        return $body;
    }

    /**
     * Retrieve a request header value.
     */
    public function header(string $key, ?string $default = null): ?string
    {
        $normalizedKey = str_replace('-', '_', strtoupper($key));
        
        $serverKey = 'HTTP_' . $normalizedKey;
        if (isset($_SERVER[$serverKey])) {
            return $_SERVER[$serverKey];
        }

        if (isset($_SERVER[$normalizedKey])) {
            return $_SERVER[$normalizedKey];
        }

        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            foreach ($headers as $hKey => $hVal) {
                if (strcasecmp($hKey, $key) === 0) {
                    return $hVal;
                }
            }
        }

        return $default;
    }

    /**
     * Parse and retrieve the JSON request body parameters.
     */
    protected function getJsonParams(): array
    {
        if ($this->jsonParams === null) {
            $this->jsonParams = [];
            $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
            if (str_contains($contentType, 'application/json')) {
                $raw = file_get_contents('php://input');
                $decoded = json_decode($raw ?: '', true);
                if (is_array($decoded)) {
                    $this->jsonParams = $decoded;
                }
            }
        }
        return $this->jsonParams;
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
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
               isset($_SERVER['HTTP_HX_REQUEST']);
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

    /**
     * Retrieve uploaded file metadata from $_FILES.
     * Returns the file array or null if it doesn't exist.
     */
    public function file(string $key): ?array
    {
        return $_FILES[$key] ?? null;
    }

    /**
     * Get all uploaded files.
     */
    public function getFiles(): array
    {
        return $_FILES;
    }
}
