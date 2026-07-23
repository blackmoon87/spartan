<?php

declare(strict_types=1);

namespace App\Core;

abstract class Middleware
{
    /**
     * Execute the middleware logic.
     * Throw exceptions, redirect, or modify request/response.
     */
    abstract public function execute(Request $request, Response $response): void;
}
