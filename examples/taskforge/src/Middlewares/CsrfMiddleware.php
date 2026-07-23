<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Application;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;

class CsrfMiddleware extends Middleware
{
    public function execute(Request $request, Response $response): void
    {
        if (in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return;
        }

        // Check CSRF exclusion patterns
        if (isset(Application::$app->router)) {
            $router = Application::$app->router;
            if (method_exists($router, 'isCsrfExcluded') && $router->isCsrfExcluded($request->getPath())) {
                return;
            }
        }

        if (!$request->validateCsrf()) {
            if (defined('SPARTAN_TESTING') && SPARTAN_TESTING) {
                throw new \RuntimeException("CSRF token mismatch.");
            }
            $response->setStatusCode(419);
            echo "419 — CSRF token mismatch.";
            exit;
        }
    }
}
