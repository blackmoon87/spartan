<?php

declare(strict_types=1);

namespace App\Core;

class View
{
    private string $layout = 'main';
    private string $viewsPath;
    private array $sections = [];
    private ?string $activeSection = null;
    private ?string $extendedLayout = null;

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
     */
    public function render(string $view, array $params = []): string
    {
        if (!preg_match('#^[a-zA-Z0-9_/]+$#', $view)) {
            throw new \InvalidArgumentException(
                "Invalid view name [{$view}]. Only alphanumeric characters, underscores, and forward slashes are allowed."
            );
        }

        $bladeFile = $this->viewsPath . "/{$view}.blade.php";
        if (file_exists($bladeFile)) {
            $compiledFile = $this->compile($view);
            
            // Backup the current rendering state (nested rendering protection)
            $previousLayout = $this->extendedLayout;
            $previousSections = $this->sections;
            
            $this->extendedLayout = null;
            $this->sections = [];
            
            $viewContent = $this->renderCompiled($compiledFile, $params);
            
            if ($this->extendedLayout !== null) {
                $layoutCompiled = $this->compile($this->extendedLayout);
                $viewContent = $this->renderCompiled($layoutCompiled, $params);
            }
            
            // Restore previous rendering state
            $this->extendedLayout = $previousLayout;
            $this->sections = $previousSections;
            
            return $viewContent;
        }

        $viewContent = $this->renderOnlyView($view, $params);
        $layoutContent = $this->layoutContent($params);
        return str_replace('{{content}}', $viewContent, $layoutContent);
    }

    /**
     * Render only the view template content without wrapping it in a layout.
     */
    public function renderViewOnly(string $view, array $params = []): string
    {
        if (!preg_match('#^[a-zA-Z0-9_/]+$#', $view)) {
            throw new \InvalidArgumentException(
                "Invalid view name [{$view}]. Only alphanumeric characters, underscores, and forward slashes are allowed."
            );
        }

        $bladeFile = $this->viewsPath . "/{$view}.blade.php";
        if (file_exists($bladeFile)) {
            $compiledFile = $this->compile($view);
            
            // Backup the current rendering state (nested rendering protection)
            $previousLayout = $this->extendedLayout;
            $previousSections = $this->sections;
            
            $this->extendedLayout = null;
            $this->sections = [];
            
            $viewContent = $this->renderCompiled($compiledFile, $params);
            
            if ($this->extendedLayout !== null) {
                $layoutCompiled = $this->compile($this->extendedLayout);
                $viewContent = $this->renderCompiled($layoutCompiled, $params);
            }
            
            // Restore previous rendering state
            $this->extendedLayout = $previousLayout;
            $this->sections = $previousSections;
            
            return $viewContent;
        }

        return $this->renderOnlyView($view, $params);
    }

    /**
     * Compile a Blade template.
     */
    protected function compile(string $view): string
    {
        $viewFile = str_replace('.', '/', $view);
        $sourcePath = $this->viewsPath . "/{$viewFile}.blade.php";
        if (!file_exists($sourcePath)) {
            throw new \InvalidArgumentException("Blade template not found [{$view}]");
        }

        $cacheDir = dirname(dirname(__DIR__)) . '/storage/views';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        $compiledPath = $cacheDir . '/' . md5($view) . '.php';

        $viewsConfig = isset(Application::$app) ? (Application::$app->config['views'] ?? []) : [];
        $cacheEnabled = $viewsConfig['cache_enabled'] ?? false;

        if ($cacheEnabled && file_exists($compiledPath)) {
            return $compiledPath;
        }

        $debugMode = getenv('APP_DEBUG') === 'true' || ($_ENV['APP_DEBUG'] ?? 'false') === 'true';

        if (!file_exists($compiledPath) || $debugMode || filemtime($sourcePath) > filemtime($compiledPath)) {
            $content = file_get_contents($sourcePath);
            $compiledContent = $this->compileString($content);
            file_put_contents($compiledPath, $compiledContent);
        }

        return $compiledPath;
    }

    /**
     * Translate Blade directives to PHP.
     */
    protected function compileString(string $content): string
    {
        $content = preg_replace('/\{\{\s*(.+?)\s*\}\}/s', '<?php echo htmlspecialchars(($1) ?? \'\', ENT_QUOTES, \'UTF-8\'); ?>', $content);
        $content = preg_replace('/\{!!\s*(.+?)\s*!!\}/s', '<?php echo $1; ?>', $content);
        $content = preg_replace('/@extends\s*\((.*?)\)/', '<?php $this->extend($1); ?>', $content);
        $content = preg_replace('/@section\s*\((.*?)\)/', '<?php $this->startSection($1); ?>', $content);
        $content = preg_replace('/@endsection/', '<?php $this->endSection(); ?>', $content);
        $content = preg_replace('/@yield\s*\((.*?)\)/', '<?php echo $this->yieldContent($1); ?>', $content);
        $content = preg_replace('/@include\s*\((.*?)\)/', '<?php echo $this->include($1, get_defined_vars()); ?>', $content);
        $content = preg_replace('/@csrf/', '<?php echo $this->csrfToken(); ?>', $content);
        
        $content = preg_replace('/@flash\s*(\((?>[^()]+|(?1))*\))/', '<?php echo $this->flash$1; ?>', $content);
        $content = preg_replace('/@escape\s*(\((?>[^()]+|(?1))*\))/', '<?php echo $this->escape$1; ?>', $content);
        
        $content = preg_replace('/@if\s*(\((?>[^()]+|(?1))*\))/', '<?php if$1: ?>', $content);
        $content = preg_replace('/@elseif\s*(\((?>[^()]+|(?1))*\))/', '<?php elseif$1: ?>', $content);
        $content = preg_replace('/@else/', '<?php else: ?>', $content);
        $content = preg_replace('/@endif/', '<?php endif; ?>', $content);

        $content = preg_replace('/@foreach\s*(\((?>[^()]+|(?1))*\))/', '<?php foreach$1: ?>', $content);
        $content = preg_replace('/@endforeach/', '<?php endforeach; ?>', $content);

        $content = preg_replace('/@for\s*(\((?>[^()]+|(?1))*\))/', '<?php for$1: ?>', $content);
        $content = preg_replace('/@endfor/', '<?php endfor; ?>', $content);

        $content = preg_replace('/@while\s*(\((?>[^()]+|(?1))*\))/', '<?php while$1: ?>', $content);
        $content = preg_replace('/@endwhile/', '<?php endwhile; ?>', $content);

        $content = preg_replace('/@empty\s*(\((?>[^()]+|(?1))*\))/', '<?php if(empty$1): ?>', $content);
        $content = preg_replace('/@endempty/', '<?php endif; ?>', $content);

        return $content;
    }

    /**
     * Run compiled template inside local Closure.
     */
    protected function renderCompiled(string $compiledFile, array $params): string
    {
        $renderer = \Closure::bind(function() use ($compiledFile, $params) {
            extract($params, EXTR_SKIP);
            ob_start();
            require $compiledFile;
            return (string) ob_get_clean();
        }, $this, static::class);

        return $renderer();
    }

    /**
     * Section layout helpers.
     */
    public function extend(string $layout): void
    {
        $this->extendedLayout = trim($layout, '\'"');
    }

    public function startSection(string $name): void
    {
        $this->activeSection = trim($name, '\'"');
        ob_start();
    }

    public function endSection(): void
    {
        if ($this->activeSection === null) {
            return;
        }
        $this->sections[$this->activeSection] = ob_get_clean();
        $this->activeSection = null;
    }

    public function yieldContent(string $name): string
    {
        $name = trim($name, '\'"');
        return $this->sections[$name] ?? '';
    }

    public function include(string $view, array $params = [], array $localVars = []): string
    {
        $view = trim($view, '\'"');
        unset($localVars['this']);
        return $this->renderViewOnly($view, array_merge($localVars, $params));
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
        $key = trim($key, '\'"');
        return Application::$app->session->getFlash($key, $default);
    }

    /**
     * Load layout contents.
     */
    protected function layoutContent(array $params = []): string
    {
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
     */
    protected function renderOnlyView(string $view, array $params): string
    {
        if (!preg_match('#^[a-zA-Z0-9_/]+$#', $view)) {
            throw new \InvalidArgumentException(
                "Invalid view name [{$view}]. Only alphanumeric characters, underscores, and forward slashes are allowed."
            );
        }

        $renderer = \Closure::bind(function() use ($view, $params) {
            extract($params, EXTR_SKIP);
            $viewPath = $this->viewsPath . "/{$view}.php";

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
