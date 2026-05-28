<?php

declare(strict_types=1);

namespace App\Core;

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
    public Container       $container;
    public EventDispatcher $events;
    public ?\PDO $db = null;
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
        $this->response  = new Response();
        $this->view      = new View();
        $this->router    = new Router($this->request, $this->response);

        // Boot cache driver
        Cache::boot($config['cache'] ?? []);

        // Generate a cryptographically secure CSRF token if not already in session
        if (!$this->session->get('_csrf_token')) {
            $this->session->set('_csrf_token', bin2hex(random_bytes(32)));
        }

        // Connect database if configurations are provided
        if (!empty($config['db']['database'])) {
            try {
                $this->db = Database::getInstance($config['db']);
            } catch (\PDOException $e) {
                error_log("Database connection failed during boot: " . $e->getMessage());
            }
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
}
