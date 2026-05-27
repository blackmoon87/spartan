<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    protected Request $request;
    protected Response $response;
    protected array $routes = [];

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
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
        if ($method === 'POST' && !$this->request->validateCsrf()) {
            $this->response->setStatusCode(403);
            if ($this->request->isAjax()) {
                $this->response->json(['error' => 'Invalid or expired CSRF token.'], 403);
            }
            throw new \RuntimeException("CSRF token validation failed.");
        }

        // 2. Execute Middlewares
        foreach ($routeData['middlewares'] as $middlewareClass) {
            /** @var Middleware $middleware */
            $middleware = new $middlewareClass();
            $middleware->execute($this->request, $this->response);
        }

        // 3. Execute Callback
        return $this->executeCallback($routeData['callback'], $params);
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
            
            $controller = new $controllerClass();
            if (!method_exists($controller, $action)) {
                throw new \BadMethodCallException("Method [{$action}] does not exist on controller [{$controllerClass}].");
            }
            
            return call_user_func([$controller, $action], ...$params);
        }

        throw new \RuntimeException("Invalid route callback type.");
    }
}
