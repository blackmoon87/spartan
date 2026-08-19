<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use App\Core\Application;

/**
 * Example Auth Middleware — blocks unauthenticated access to protected routes.
 *
 * Register in public/index.php:
 *   $app->router->get('/admin', [AdminController::class, 'index'], [AuthMiddleware::class]);
 */
class AuthMiddleware extends Middleware
{
    public function execute(Request $request, Response $response): void
    {
        $userId = Application::$app->session->get('user_id');

        if (!$userId) {
            Application::$app->session->setFlash('error', 'You must be logged in to access this page.');
            $response->redirect('/login');
        }
    }
}
