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
    public Router    $router;
    public Request   $request;
    public Response  $response;
    public View      $view;
    public Session   $session;
    public Container       $container;
    public EventDispatcher $events;
    public ?\PDO $db = null;
    public array $config;

    public function __construct(array $config)
    {
        // Enforce single instantiation — prevents accidental double-boot
        if (isset(self::$app)) {
            throw new \LogicException('Application has already been instantiated. Only one instance is allowed per process.');
        }

        self::$app = $this;
        $this->config    = $config;
        $this->container = new Container();
        $this->events    = new EventDispatcher();
        $this->session   = new Session();
        $this->request   = new Request();
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

    /**
     * Start the routing system and output the response content.
     */
    public function run(): void
    {
        try {
            echo $this->router->resolve();
        } catch (\Exception $e) {
            $this->response->setStatusCode(500);
            if ($this->config['app']['debug'] ?? false) {
                echo "<h1>500 Internal Server Error</h1>";
                echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
                echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            } else {
                echo "<h1>500 Internal Server Error</h1>";
            }
        } finally {
            // Automatically clean flash messages at the end of execution
            $this->session->removeFlashMessages();
        }
    }
}
