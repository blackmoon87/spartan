<?php

declare(strict_types=1);

namespace App\Core;

/**
 * App Orchestrator
 * 
 * @property \PDO|null $db Database connection instance
 */
class Application
{
    /**
     * Readonly after first assignment — prevents any external code from
     * overwriting the global application instance post-boot.
     * PHP 8.1+ readonly enforcement.
     */
    public static Application $app;
    public Logger    $logger;
    public Router    $router;
    public Request   $request;
    public Response  $response;
    public ViewInterface     $view;
    public SessionInterface  $session;
    public AuthInterface     $auth;
    public Container       $container;
    public EventDispatcher $events;
    private ?\PDO $dbInstance = null;
    public array $config;

    public function __construct(array $config)
    {
        require_once __DIR__ . '/helpers.php';
        // Enforce single instantiation — prevents accidental double-boot
        if (isset(self::$app)) {
            throw new \LogicException('Application has already been instantiated. Only one instance is allowed per process.');
        }

        self::$app = $this;
        $this->config    = $config;
        $this->container = new Container();
        $this->logger    = new Logger();
        
        // Register logger in container
        $this->container->singleton(Logger::class, fn() => $this->logger);

        $this->events    = new EventDispatcher();
        $this->request   = new Request();
        $this->session   = new Session($this->request);
        $this->auth      = new Auth($this->session);
        $this->response  = new Response();
        $this->view      = new View();
        $this->router    = new Router($this->request, $this->response);

        // Register AuthInterface in container
        $this->container->singleton(AuthInterface::class, fn() => $this->auth);

        // Boot cache driver
        Cache::boot($config['cache'] ?? []);

        // Generate a cryptographically secure CSRF token if not already in session
        if (!$this->session->get('_csrf_token')) {
            $this->session->set('_csrf_token', bin2hex(random_bytes(32)));
        }
    }

    public function run(): void
    {
        try {
            $result = $this->router->resolve();
            if ($result instanceof Response) {
                $result->send();
            } else {
                if ($result !== null && $result !== '') {
                    $this->response->send();
                    echo $result;
                } else {
                    $this->response->send();
                }
            }
        } catch (\Throwable $e) {
            $handler = $this->container->has(ExceptionHandler::class)
                ? $this->container->make(ExceptionHandler::class)
                : new ExceptionHandler();
                
            $handler->handle($e, $this->request, $this->response, $this->config);
        } finally {
            // Automatically clean flash messages at the end of execution
            $this->session->removeFlashMessages();
        }
    }

    /**
     * Handle incoming request for Worker Mode (FrankenPHP, RoadRunner, Swoole)
     * Resets transient per-request state while retaining booted services.
     */
    public function handleRequest(?Request $request = null): void
    {
        if ($request !== null) {
            $this->request = $request;
            $this->router->setRequest($request);
        }
        $this->response = new Response();
        $this->router->setResponse($this->response);

        $this->run();

        // Reset transient state post execution to prevent memory accumulation
        $this->resetPerRequestState();
    }

    /**
     * Reset per-request state to prevent memory leak in worker mode.
     */
    public function resetPerRequestState(): void
    {
        // Clear container non-singleton transient instances if any
        if (isset($this->session)) {
            $this->session->removeFlashMessages();
        }
    }

    /**
     * Magic getter to support lazy-loading of the database connection.
     */
    public function __get(string $name)
    {
        if ($name === 'db') {
            if ($this->dbInstance === null && !empty($this->config['db']['database'])) {
                try {
                    $this->dbInstance = Database::getInstance($this->config['db']);
                } catch (\PDOException $e) {
                    error_log("Database connection failed during lazy boot: " . $e->getMessage());
                }
            }
            return $this->dbInstance;
        }
        return null;
    }

    /**
     * Magic setter to allow swapping the db instance (useful in tests).
     */
    public function __set(string $name, mixed $value): void
    {
        if ($name === 'db') {
            $this->dbInstance = $value;
        }
    }

    /**
     * Magic isset to check if database is connected or configured.
     */
    public function __isset(string $name): bool
    {
        if ($name === 'db') {
            return $this->dbInstance !== null || !empty($this->config['db']['database']);
        }
        return false;
    }
}
