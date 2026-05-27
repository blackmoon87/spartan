<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Simple Service Container with auto-resolution and singleton support.
 *
 * Usage:
 *   // Bind a factory
 *   Application::$app->container->bind(MailService::class, fn() => new SmtpMailer(config('mail')));
 *
 *   // Bind a singleton (same instance every call)
 *   Application::$app->container->singleton(PaymentService::class, fn() => new StripeGateway());
 *
 *   // Resolve
 *   $mailer = Application::$app->container->make(MailService::class);
 *
 *   // Controller shortcut
 *   $mailer = $this->make(MailService::class);
 */
class Container
{
    /** @var array<string, callable> Registered bindings */
    private array $bindings = [];

    /** @var array<string, mixed> Resolved singleton instances */
    private array $instances = [];

    // ─── Registration ──────────────────────────────────────────────────────────

    /**
     * Register a factory binding.
     * A new instance is created on every make() call.
     */
    public function bind(string $abstract, callable $factory): void
    {
        $this->bindings[$abstract] = $factory;
    }

    /**
     * Register a singleton binding.
     * The same instance is returned on every make() call.
     */
    public function singleton(string $abstract, callable $factory): void
    {
        // Wrap factory to cache instance on first resolution
        $this->bindings[$abstract] = function () use ($abstract, $factory) {
            if (!isset($this->instances[$abstract])) {
                $this->instances[$abstract] = $factory($this);
            }
            return $this->instances[$abstract];
        };
    }

    /**
     * Register an already-constructed instance as a singleton.
     */
    public function instance(string $abstract, mixed $instance): void
    {
        $this->instances[$abstract] = $instance;
        $this->bindings[$abstract] = fn() => $instance;
    }

    // ─── Resolution ────────────────────────────────────────────────────────────

    /**
     * Resolve and return an instance of the given abstract.
     * Falls back to auto-resolution via Reflection if no binding is registered.
     *
     * @throws \RuntimeException if the class cannot be resolved
     */
    public function make(string $abstract): mixed
    {
        // Registered binding takes priority
        if (isset($this->bindings[$abstract])) {
            return ($this->bindings[$abstract])($this);
        }

        // Auto-resolution via Reflection (constructor injection)
        return $this->autoResolve($abstract);
    }

    /**
     * Check if an abstract has been registered in the container.
     */
    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    /**
     * Remove a binding (useful in tests).
     */
    public function forget(string $abstract): void
    {
        unset($this->bindings[$abstract], $this->instances[$abstract]);
    }

    // ─── Auto Resolution ───────────────────────────────────────────────────────

    /**
     * Attempt to auto-resolve a class by inspecting its constructor
     * parameters and recursively resolving each dependency.
     */
    private function autoResolve(string $abstract): mixed
    {
        if (!class_exists($abstract)) {
            throw new \RuntimeException(
                "Container: Cannot resolve [{$abstract}]. No binding registered and class does not exist."
            );
        }

        $reflector = new \ReflectionClass($abstract);

        if (!$reflector->isInstantiable()) {
            throw new \RuntimeException(
                "Container: [{$abstract}] is not instantiable (abstract class or interface). Register a binding."
            );
        }

        $constructor = $reflector->getConstructor();

        // No constructor — just instantiate directly
        if ($constructor === null) {
            return new $abstract();
        }

        $dependencies = array_map(function (\ReflectionParameter $param) use ($abstract) {
            $type = $param->getType();

            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                return $this->make($type->getName());
            }

            if ($param->isDefaultValueAvailable()) {
                return $param->getDefaultValue();
            }

            throw new \RuntimeException(
                "Container: Cannot resolve parameter [{$param->getName()}] in [{$abstract}]. "
              . "Bind it explicitly or provide a default value."
            );
        }, $constructor->getParameters());

        return $reflector->newInstanceArgs($dependencies);
    }
}
