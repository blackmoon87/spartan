<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use App\Core\Application;

class CsrfMiddleware extends Middleware
{
    public function execute(Request $request, Response $response): void
    {
        $method = $request->getRealMethod();
        if ($method !== 'POST' && $request->getMethod() === 'POST') {
            $method = 'POST';
        }
        $path = $request->getPath();

        // Check if the request is a state-changing method
        if ($method === 'POST') {
            $isExcluded = false;
            
            // Access CSRF exclusions from the router
            $router = Application::$app->router;
            $exclusions = method_exists($router, 'getCsrfExclusions') ? $router->getCsrfExclusions() : [];
            
            foreach ($exclusions as $excludedPath) {
                $pattern = str_replace('\*', '.*', preg_quote($excludedPath, '#'));
                if (preg_match('#^' . $pattern . '$#', $path)) {
                    $isExcluded = true;
                    break;
                }
            }

            if (!$isExcluded && !$request->validateCsrf()) {
                $response->setStatusCode(403);
                if ($request->isAjax()) {
                    $response->json(['error' => 'Invalid or expired CSRF token.'], 403);
                    return;
                }
                throw new \RuntimeException("CSRF token validation failed.");
            }
        }
    }
}
