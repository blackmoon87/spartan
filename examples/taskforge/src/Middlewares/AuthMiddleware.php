<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Application;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;

class AuthMiddleware extends Middleware
{
    public function execute(Request $request, Response $response): void
    {
        $session = Application::$app->session;
        $userId = $session->get('user_id');

        if (!$userId) {
            if (defined('SPARTAN_TESTING') && SPARTAN_TESTING) {
                throw new \RuntimeException("Unauthorized: no active session.");
            }
            $session->setFlash('warning', 'Please log in to continue.');
            $response->redirect('/login');
            $response->send();
            exit;
        }
    }
}
