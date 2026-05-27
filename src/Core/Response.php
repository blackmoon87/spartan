<?php

declare(strict_types=1);

namespace App\Core;

class Response
{
    protected int $statusCode = 200;
    protected array $headers = [];
    protected ?string $content = null;
    protected ?string $redirectUrl = null;

    /**
     * Set the HTTP response status code.
     */
    public function setStatusCode(int $code): void
    {
        $this->statusCode = $code;
    }

    /**
     * Get the current HTTP response status code.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Set a custom header.
     */
    public function setHeader(string $name, string $value): void
    {
        $this->headers[$name] = $value;
    }

    /**
     * Get all custom headers.
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get the response content.
     */
     public function getContent(): ?string
     {
         return $this->content;
     }

    /**
     * Set the response content.
     */
    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    /**
     * Get the redirect URL.
     */
    public function getRedirectUrl(): ?string
    {
        return $this->redirectUrl;
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

        $this->redirectUrl = $url;
        $this->setHeader('Location', $url);
    }

    /**
     * Send a JSON response.
     */
    public function json(mixed $data, int $statusCode = 200): void
    {
        $this->setStatusCode($statusCode);
        $this->setHeader('Content-Type', 'application/json; charset=utf-8');
        $this->content = json_encode($data);
    }

    /**
     * Send the status code, headers, and body.
     */
    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->statusCode);
            foreach ($this->headers as $name => $value) {
                header($name . ': ' . $value);
            }
        }

        if ($this->content !== null) {
            echo $this->content;
        }
    }

    /**
     * Reset response properties (useful for testing).
     */
    public function reset(): void
    {
        $this->statusCode = 200;
        $this->headers = [];
        $this->content = null;
        $this->redirectUrl = null;
    }
}
