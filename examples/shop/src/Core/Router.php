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
    protected array $globalMiddlewares = [];

    /** Memoised {param} -> regex compilations, keyed by route path. */
    protected array $patternCache = [];

    /** Memoised attribute scan results, keyed by "Class::method". */
    protected static array $authAttributeCache = [];

    /** Memoised named-argument compatibility per callback + placeholder set. */
    protected static array $namedArgCache = [];

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Set global middlewares that run on every request.
     */
    public function setGlobalMiddlewares(array $middlewares): void
    {
        $this->globalMiddlewares = $middlewares;
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
     * Dynamically swap responses.
     */
    public function setResponse(Response $response): void
    {
        $this->response = $response;
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
     * Get CSRF exclusions.
     */
    public function getCsrfExclusions(): array
    {
        return $this->csrfExclusions;
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

        $routeData = $this->routes[$method][$path] ?? false;
        $params    = [];
        // Only dynamic routes carry params, so only they need a cache key.
        $routeKey  = '';

        // If direct match not found, try dynamic pattern matching.
        // Static routes are skipped — the exact-match lookup above already
        // ruled them out, so only routes carrying {placeholders} are scanned.
        if ($routeData === false) {
            foreach ($this->routes[$method] ?? [] as $routePath => $data) {
                if (!str_contains($routePath, '{')) {
                    continue;
                }

                $pattern = $this->patternCache[$routePath]
                    ??= '#^' . preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $routePath) . '$#';

                if (preg_match($pattern, $path, $matches)) {
                    $params    = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                    $routeData = $data;
                    $routeKey  = $method . ' ' . $routePath;
                    break;
                }
            }
        }

        // Global middlewares run on EVERY request — including unmatched ones,
        // so security headers and rate limits still apply to 404 traffic.
        if ($this->runMiddlewares($this->globalMiddlewares)) {
            return $this->response;
        }

        if ($routeData === false) {
            $this->response->setStatusCode(404);
            return Application::$app->view->render('error_404', ['message' => 'The page you requested was not found.']);
        }

        // Execute route middlewares
        if ($this->runMiddlewares($routeData['middlewares'])) {
            return $this->response;
        }

        // Execute Callback
        return $this->executeCallback($routeData['callback'], $params, $routeKey);
    }

    /**
     * Run a middleware stack.
     * Returns true when the chain terminated the request (redirect, content,
     * or an error status), meaning the route callback must NOT run.
     */
    protected function runMiddlewares(array $middlewares): bool
    {
        if ($middlewares === []) {
            return false;
        }

        foreach ($this->resolveMiddlewares($middlewares) as $middlewareInfo) {
            $middlewareClass = $middlewareInfo['class'];
            $args            = $middlewareInfo['args'];

            if (!class_exists($middlewareClass)) {
                throw new \InvalidArgumentException("Middleware [{$middlewareClass}] does not exist.");
            }

            // Argument-less middlewares go through the container so they can
            // declare constructor dependencies; parameterised ones are built
            // directly (no reflection cost on the hot path).
            $middleware = $args === [] && Application::$app->container->has($middlewareClass)
                ? Application::$app->container->make($middlewareClass)
                : new $middlewareClass(...$args);

            $middleware->execute($this->request, $this->response);

            if ($this->response->getRedirectUrl() !== null
                || $this->response->getContent() !== null
                || $this->response->getStatusCode() >= 400) {
                return true;
            }
        }

        return false;
    }

    /**
     * Helper to resolve middleware groups and class names to a flat array.
     *
     * @param list<string> $seenGroups Guards against self-referencing groups.
     */
    protected function resolveMiddlewares(array $middlewares, array $seenGroups = []): array
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
                    if (in_array($name, $seenGroups, true)) {
                        throw new \LogicException(
                            "Circular middleware group reference detected: '{$name}' includes itself."
                        );
                    }
                    $resolved = array_merge(
                        $resolved,
                        $this->resolveMiddlewares($this->middlewareGroups[$name], [...$seenGroups, $name])
                    );
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
    protected function executeCallback(mixed $callback, array $params = [], string $routeKey = ''): mixed
    {
        if (is_callable($callback)) {
            // Route params are keyed by placeholder name. Spreading a string-keyed
            // array passes NAMED arguments, which fatals unless the closure happens
            // to use identical parameter names — so only do that when the names
            // actually line up, otherwise fall back to positional order.
            if ($params !== [] && !$this->callbackAcceptsNamed($callback, $params, $routeKey)) {
                $params = array_values($params);
            }
            return call_user_func($callback, ...$params);
        }

        if (is_array($callback)) {
            $controllerClass = $callback[0];
            $action = $callback[1];
            
            if (!class_exists($controllerClass)) {
                throw new \InvalidArgumentException("Controller class [{$controllerClass}] does not exist.");
            }

            // Verify the action BEFORE reflecting on attributes — otherwise a
            // typo'd route surfaced as a raw ReflectionException instead of
            // this explicit error.
            if (!method_exists($controllerClass, $action)) {
                throw new \BadMethodCallException("Method [{$action}] does not exist on controller [{$controllerClass}].");
            }

            if (!$this->checkAuthorizationAttributes($controllerClass, $action)) {
                return $this->response;
            }

            $controller = Application::$app->container->make($controllerClass);

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
                } elseif ($parameter->isDefaultValueAvailable()) {
                    $actionArgs[] = $parameter->getDefaultValue();
                } elseif ($type === null || $parameter->allowsNull()) {
                    $actionArgs[] = null;
                } else {
                    // Previously injected null here, surfacing as an opaque
                    // TypeError deep inside the controller.
                    throw new \InvalidArgumentException(
                        "Route parameter [\${$name}] required by {$controllerClass}::{$action}() "
                        . "was not supplied by the matched route pattern."
                    );
                }
            }
            
            return call_user_func_array([$controller, $action], $actionArgs);
        }

        throw new \RuntimeException("Invalid route callback type.");
    }

    /**
     * Do the callable's parameter names cover every route placeholder name?
     * Reflection runs once per callback + placeholder set, then is memoised —
     * this sits on the hot path of every dynamic route.
     */
    protected function callbackAcceptsNamed(callable $callback, array $params, string $routeKey = ''): bool
    {
        // The matched route is a stable, cheap cache key: one array lookup per
        // request instead of rebuilding a signature string.
        if ($routeKey !== '') {
            return self::$namedArgCache[$routeKey] ??= $this->resolveAcceptsNamed($callback, $params);
        }

        $cacheKey = match (true) {
            $callback instanceof \Closure => 'c' . spl_object_id($callback),
            is_array($callback)           => (is_object($callback[0]) ? get_class($callback[0]) : (string) $callback[0]) . '::' . $callback[1],
            is_string($callback)          => $callback,
            default                       => 'o' . spl_object_id($callback),
        } . '|' . implode(',', array_keys($params));

        if (isset(self::$namedArgCache[$cacheKey])) {
            return self::$namedArgCache[$cacheKey];
        }

        return self::$namedArgCache[$cacheKey] = $this->resolveAcceptsNamed($callback, $params);
    }

    /**
     * Uncached reflection behind callbackAcceptsNamed().
     */
    private function resolveAcceptsNamed(callable $callback, array $params): bool
    {
        try {
            $ref = $callback instanceof \Closure || is_string($callback)
                ? new \ReflectionFunction($callback)
                : new \ReflectionMethod(...(is_array($callback) ? $callback : [$callback, '__invoke']));
        } catch (\ReflectionException) {
            return false;
        }

        $names = [];
        foreach ($ref->getParameters() as $p) {
            $names[$p->getName()] = true;
        }

        foreach (array_keys($params) as $key) {
            if (!isset($names[$key])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Scan and verify authorization attributes on the controller class and action method.
     */
    protected function checkAuthorizationAttributes(string $controllerClass, string $action): bool
    {
        // Reflection is expensive and the result never changes for a given
        // class/method, so the requirement set is resolved once per process.
        $cacheKey = $controllerClass . '::' . $action;
        if (!isset(self::$authAttributeCache[$cacheKey])) {
            $classRef  = new \ReflectionClass($controllerClass);
            $methodRef = new \ReflectionMethod($controllerClass, $action);

            $requiredRoles = [];
            foreach (array_merge(
                $classRef->getAttributes(\App\Core\Attributes\RequireRole::class),
                $methodRef->getAttributes(\App\Core\Attributes\RequireRole::class)
            ) as $attr) {
                $requiredRoles = array_merge($requiredRoles, $attr->newInstance()->roles);
            }

            $requiredPermissions = [];
            foreach (array_merge(
                $classRef->getAttributes(\App\Core\Attributes\RequirePermission::class),
                $methodRef->getAttributes(\App\Core\Attributes\RequirePermission::class)
            ) as $attr) {
                $requiredPermissions = array_merge($requiredPermissions, $attr->newInstance()->permissions);
            }

            self::$authAttributeCache[$cacheKey] = [$requiredRoles, $requiredPermissions];
        }

        [$requiredRoles, $requiredPermissions] = self::$authAttributeCache[$cacheKey];

        if (empty($requiredRoles) && empty($requiredPermissions)) {
            return true;
        }

        // Resolve authenticated user
        $user = null;
        if (Application::$app->container->has('auth_user')) {
            $user = Application::$app->container->make('auth_user');
        } else {
            $userId = Application::$app->session->get('user_id');
            if ($userId) {
                $userClass = 'App\\Models\\User';
                if (class_exists($userClass)) {
                    try {
                        $userModel = new $userClass();
                        $user = $userModel->findInstance($userId);
                        if ($user) {
                            Application::$app->container->instance('auth_user', $user);
                        }
                    } catch (\Throwable $e) {
                    }
                }
            }
        }

        if (!$user) {
            $this->response->setStatusCode(401);
            if ($this->request->isAjax()) {
                $this->response->json(['error' => 'Unauthorized.'], 401);
            } else {
                Application::$app->session->setFlash('error', 'You must be logged in to access this page.');
                $this->response->redirect('/login');
            }
            return false;
        }

        // Verify Roles
        if (!empty($requiredRoles)) {
            if (!method_exists($user, 'hasRole') || !$user->hasRole(...$requiredRoles)) {
                $this->abortForbidden();
                return false;
            }
        }

        // Verify Permissions
        if (!empty($requiredPermissions)) {
            if (!method_exists($user, 'hasPermission')) {
                $this->abortForbidden();
                return false;
            }
            foreach ($requiredPermissions as $permission) {
                if (!$user->hasPermission($permission)) {
                    $this->abortForbidden();
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Set Response content for forbidden access.
     */
    protected function abortForbidden(): void
    {
        $this->response->setStatusCode(403);
        if ($this->request->isAjax()) {
            $this->response->json(['error' => 'Forbidden.'], 403);
        } else {
            try {
                $rendered = Application::$app->view->render('error_403', ['message' => 'You do not have the required permissions to access this page.']);
                $this->response->setContent($rendered);
            } catch (\Throwable $e) {
                $this->response->setContent("<h1>403 Forbidden</h1><p>You do not have the required permissions to access this page.</p>");
            }
        }
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
                $this->patternCache = [];
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

        // Write atomically — a concurrent request must never `require` a
        // half-written cache file.
        $tmp = $file . '.' . getmypid() . '.tmp';
        if (file_put_contents($tmp, $content, LOCK_EX) === false) {
            return false;
        }
        if (!rename($tmp, $file)) {
            @unlink($tmp);
            return false;
        }
        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate($file, true);
        }
        return true;
    }
}
