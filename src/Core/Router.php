<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    protected Request $request;
    protected Response $response;
    protected array $routes = [];
    protected array $middlewareGroups = [];
    protected array $csrfExclusions = [];
    protected array $middlewareAliases = [];

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
        
        $this->middlewareAliases = [
            'auth' => \App\Middlewares\AuthMiddleware::class,
            'rate_limit' => \App\Middlewares\RateLimitMiddleware::class,
        ];
    }

    /**
     * Define a middleware alias.
     */
    public function aliasMiddleware(string $name, string $class): void
    {
        $this->middlewareAliases[$name] = $class;
    }

    /**
     * Dynamically swap requests.
     */
    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }

    /**
     * Define a middleware group.
     */
    public function middlewareGroup(string $name, array $middlewares): void
    {
        $this->middlewareGroups[$name] = $middlewares;
    }

    /**
     * Define CSRF exclusions.
     */
    public function excludeCsrf(string ...$paths): void
    {
        $this->csrfExclusions = array_merge($this->csrfExclusions, $paths);
    }

    /**
     * Define a GET route.
     */
    public function get(string $path, array|callable $callback, array $middlewares = []): void
    {
        $this->routes['GET'][$path] = [
            'callback' => $callback,
            'middlewares' => $middlewares
        ];
    }

    /**
     * Define a POST route.
     */
    public function post(string $path, array|callable $callback, array $middlewares = []): void
    {
        $this->routes['POST'][$path] = [
            'callback'    => $callback,
            'middlewares' => $middlewares,
        ];
    }

    /**
     * Define a PUT route.
     */
    public function put(string $path, array|callable $callback, array $middlewares = []): void
    {
        $this->routes['PUT'][$path] = [
            'callback'    => $callback,
            'middlewares' => $middlewares,
        ];
    }

    /**
     * Define a PATCH route.
     */
    public function patch(string $path, array|callable $callback, array $middlewares = []): void
    {
        $this->routes['PATCH'][$path] = [
            'callback'    => $callback,
            'middlewares' => $middlewares,
        ];
    }

    /**
     * Define a DELETE route.
     */
    public function delete(string $path, array|callable $callback, array $middlewares = []): void
    {
        $this->routes['DELETE'][$path] = [
            'callback'    => $callback,
            'middlewares' => $middlewares,
        ];
    }

    /**
     * Resolve the current HTTP request to its registered callback or controller action.
     */
    public function resolve(): mixed
    {
        $this->response->reset();
        $path   = $this->request->getPath();
        $method = $this->request->getMethod();

        // HTML form method spoofing: forms can only send GET/POST.
        // Add <input type="hidden" name="_method" value="PUT"> to spoof PUT/PATCH/DELETE.
        // AJAX/fetch clients send the real method and are unaffected.
        // CSRF is still enforced because the real transport is POST.
        if ($method === 'POST' && isset($_POST['_method'])) {
            $spoofed = strtoupper(trim($_POST['_method']));
            if (in_array($spoofed, ['PUT', 'PATCH', 'DELETE'], true)) {
                $method = $spoofed;
            }
        }

        $routeData = $this->routes[$method][$path] ?? false;
        $params = [];

        // If direct match not found, try dynamic pattern matching
        if ($routeData === false) {
            foreach ($this->routes[$method] ?? [] as $routePath => $data) {
                // Replace dynamic placeholders {param} with named capturing groups
                $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $routePath);
                $pattern = '#^' . $pattern . '$#';

                if (preg_match($pattern, $path, $matches)) {
                    $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                    $routeData = $data;
                    break;
                }
            }
        }

        if ($routeData === false) {
            $this->response->setStatusCode(404);
            return Application::$app->view->render('error_404', ['message' => 'The page you requested was not found.']);
        }

        // 1. Central CSRF Protection check for all state-changing POST requests
        $isExcluded = false;
        foreach ($this->csrfExclusions as $excludedPath) {
            $pattern = str_replace('\*', '.*', preg_quote($excludedPath, '#'));
            if (preg_match('#^' . $pattern . '$#', $path)) {
                $isExcluded = true;
                break;
            }
        }

        if ($method === 'POST' && !$isExcluded && !$this->request->validateCsrf()) {
            $this->response->setStatusCode(403);
            if ($this->request->isAjax()) {
                $this->response->json(['error' => 'Invalid or expired CSRF token.'], 403);
                return $this->response;
            }
            throw new \RuntimeException("CSRF token validation failed.");
        }

        // 2. Execute Middlewares
        $resolvedMiddlewares = $this->resolveMiddlewares($routeData['middlewares']);
        foreach ($resolvedMiddlewares as $middlewareInfo) {
            $middlewareClass = $middlewareInfo['class'];
            $args = $middlewareInfo['args'];

            /** @var Middleware $middleware */
            $middleware = new $middlewareClass(...$args);
            $middleware->execute($this->request, $this->response);

            // Abort resolution if response is marked to terminate (redirect, content set, or error status code)
            if ($this->response->getRedirectUrl() !== null || $this->response->getContent() !== null || $this->response->getStatusCode() >= 400) {
                return $this->response;
            }
        }

        // 3. Execute Callback
        return $this->executeCallback($routeData['callback'], $params);
    }

    /**
     * Helper to resolve middleware groups and class names to a flat array.
     */
    protected function resolveMiddlewares(array $middlewares): array
    {
        $resolved = [];
        foreach ($middlewares as $middleware) {
            if (is_string($middleware)) {
                $parts = explode(':', $middleware, 2);
                $name = $parts[0];
                $argsString = $parts[1] ?? '';
                // Cast string arguments to proper PHP types if numeric
                $args = $argsString !== '' ? array_map(function ($arg) {
                    if (is_numeric($arg)) {
                        return str_contains($arg, '.') ? (float) $arg : (int) $arg;
                    }
                    return $arg;
                }, explode(',', $argsString)) : [];

                if (isset($this->middlewareGroups[$name])) {
                    $resolved = array_merge($resolved, $this->resolveMiddlewares($this->middlewareGroups[$name]));
                } else {
                    $class = $this->middlewareAliases[$name] ?? $name;
                    $resolved[] = [
                        'class' => $class,
                        'args' => $args
                    ];
                }
            } elseif (is_array($middleware) && isset($middleware['class'])) {
                $resolved[] = $middleware;
            } else {
                $class = is_object($middleware) ? get_class($middleware) : (string)$middleware;
                $resolved[] = [
                    'class' => $class,
                    'args' => []
                ];
            }
        }
        return $resolved;
    }

    /**
     * Execute closures or Controller action mappings.
     */
    protected function executeCallback(mixed $callback, array $params = []): mixed
    {
        if (is_callable($callback)) {
            return call_user_func($callback, ...$params);
        }

        if (is_array($callback)) {
            $controllerClass = $callback[0];
            $action = $callback[1];
            
            if (!class_exists($controllerClass)) {
                throw new \InvalidArgumentException("Controller class [{$controllerClass}] does not exist.");
            }
            
            $controller = Application::$app->container->make($controllerClass);
            if (!method_exists($controller, $action)) {
                throw new \BadMethodCallException("Method [{$action}] does not exist on controller [{$controllerClass}].");
            }

            // Inspect action parameters for Request or FormRequest dependencies
            $reflection = new \ReflectionMethod($controllerClass, $action);
            $actionArgs = [];
            
            foreach ($reflection->getParameters() as $parameter) {
                $type = $parameter->getType();
                if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                    $typeName = $type->getName();
                    if ($typeName === \App\Core\Request::class || is_subclass_of($typeName, \App\Core\Request::class)) {
                        if (is_subclass_of($typeName, \App\Core\FormRequest::class)) {
                            /** @var \App\Core\FormRequest $formRequest */
                            $formRequest = new $typeName();
                            $formRequest->validate();
                            $actionArgs[] = $formRequest;
                        } else {
                            $actionArgs[] = Application::$app->request;
                        }
                        continue;
                    }
                }
                
                $name = $parameter->getName();
                if (isset($params[$name])) {
                    $actionArgs[] = $params[$name];
                } elseif (!empty($params)) {
                    $actionArgs[] = array_shift($params);
                } else {
                    $actionArgs[] = $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : null;
                }
            }
            
            return call_user_func_array([$controller, $action], $actionArgs);
        }

        throw new \RuntimeException("Invalid route callback type.");
    }

    /**
     * Load cached routes if enabled and file exists.
     */
    public function loadCache(): bool
    {
        $config = Application::$app->config['router'] ?? [];
        $enabled = $config['cache_enabled'] ?? false;
        $file = $config['cache_file'] ?? null;

        if ($enabled && $file && file_exists($file)) {
            $data = require $file;
            if (is_array($data)) {
                $this->routes = $data['routes'] ?? [];
                $this->middlewareGroups = $data['middlewareGroups'] ?? [];
                $this->csrfExclusions = $data['csrfExclusions'] ?? [];
                return true;
            }
        }
        return false;
    }

    /**
     * Save current routes map to cache file.
     */
    public function saveCache(): bool
    {
        $config = Application::$app->config['router'] ?? [];
        $enabled = $config['cache_enabled'] ?? false;
        $file = $config['cache_file'] ?? null;

        if (!$enabled || !$file) {
            return false;
        }

        // We must check if any route callback is a Closure (since Closures cannot be exported)
        foreach ($this->routes as $method => $routes) {
            foreach ($routes as $path => $data) {
                if ($data['callback'] instanceof \Closure) {
                    throw new \LogicException("Cannot cache routes because route '{$method} {$path}' uses a Closure.");
                }
            }
        }

        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $data = [
            'routes' => $this->routes,
            'middlewareGroups' => $this->middlewareGroups,
            'csrfExclusions' => $this->csrfExclusions,
        ];

        $content = "<?php\n\n// Auto-generated route cache file\nreturn " . var_export($data, true) . ";\n";
        file_put_contents($file, $content);
        return true;
    }
}
