<?php

declare(strict_types=1);

namespace App\Core;

class View
{
    private string $layout = 'main';
    private string $viewsPath;

    public function __construct(?string $viewsPath = null)
    {
        $this->viewsPath = $viewsPath ?: dirname(__DIR__) . '/Views';
    }

    /**
     * Get the current views directory path.
     */
    public function getViewsPath(): string
    {
        return $this->viewsPath;
    }

    /**
     * Set the current views directory path.
     */
    public function setViewsPath(string $path): void
    {
        $this->viewsPath = rtrim($path, '/\\');
    }

    /**
     * Set the current rendering layout name.
     */
    public function setLayout(string $layout): void
    {
        $this->layout = $layout;
    }

    /**
     * Render the view merged with the layout content.
     * Params are passed raw but must always be output via $this->escape() in templates.
     */
    public function render(string $view, array $params = []): string
    {
        $viewContent = $this->renderOnlyView($view, $params);
        $layoutContent = $this->layoutContent($params);
        return str_replace('{{content}}', $viewContent, $layoutContent);
    }

    /**
     * Expose a secure CSRF hidden input field for forms.
     */
    public function csrfToken(): string
    {
        $token = Application::$app->session->get('_csrf_token') ?? '';
        return '<input type="hidden" name="_csrf" value="' . $token . '">';
    }

    /**
     * Retrieve the current CSRF token string.
     */
    public function csrfTokenValue(): string
    {
        return Application::$app->session->get('_csrf_token') ?? '';
    }

    /**
     * Escape output HTML content safely.
     */
    public function escape(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Fetch a temporary flash message.
     */
    public function flash(string $key, ?string $default = null): ?string
    {
        return Application::$app->session->getFlash($key, $default);
    }

    /**
     * Load layout contents.
     * Variables are injected as-is; all output inside layout files
     * MUST go through $this->escape() to prevent XSS.
     */
    protected function layoutContent(array $params = []): string
    {
        // Bind $this to the view scope so layout files can call $this->escape(), $this->flash() etc.
        $renderer = \Closure::bind(function() use ($params) {
            extract($params, EXTR_SKIP);
            $layoutPath = $this->viewsPath . "/layouts/{$this->layout}.php";
            if (!file_exists($layoutPath)) {
                return '{{content}}';
            }
            ob_start();
            include $layoutPath;
            return (string) ob_get_clean();
        }, $this, static::class);

        return $renderer();
    }

    /**
     * Load page-specific view contents.
     * Variables are injected as-is; all output inside view files
     * MUST go through $this->escape() to prevent XSS.
     */
    protected function renderOnlyView(string $view, array $params): string
    {
        // Guard: only allow safe view names (alphanumeric, underscores, slashes for subfolders)
        // Blocks path traversal attempts like '../../config/config'
        if (!preg_match('#^[a-zA-Z0-9_/]+$#', $view)) {
            throw new \InvalidArgumentException(
                "Invalid view name [{$view}]. Only alphanumeric characters, underscores, and forward slashes are allowed."
            );
        }

        $renderer = \Closure::bind(function() use ($view, $params) {
            extract($params, EXTR_SKIP);
            $viewPath = $this->viewsPath . "/{$view}.php";

            // Secondary guard: resolved path must stay inside the Views directory
            $viewsBase = realpath($this->viewsPath);
            $resolvedPath = realpath($viewPath);
            if ($resolvedPath === false || $viewsBase === false || !str_starts_with($resolvedPath, $viewsBase)) {
                return "View [{$view}] not found.";
            }

            ob_start();
            include $resolvedPath;
            return (string) ob_get_clean();
        }, $this, static::class);

        return $renderer();
    }
}
