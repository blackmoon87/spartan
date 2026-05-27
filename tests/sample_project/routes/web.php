<?php

declare(strict_types=1);

use Tests\Sample\Controllers\DashboardController;

/** @var \App\Core\Application $app */

$app->router->get('/', [DashboardController::class, 'index']);
$app->router->post('/order', [DashboardController::class, 'storeOrder']);
$app->router->post('/user', [DashboardController::class, 'storeUser']);
$app->router->put('/order/{id}', [DashboardController::class, 'updateOrder']);
$app->router->delete('/order/{id}', [DashboardController::class, 'destroyOrder']);
$app->router->get('/redirect-test', [DashboardController::class, 'redirectTest']);
$app->router->get('/search', [DashboardController::class, 'searchPage']);
$app->router->post('/search/query', [DashboardController::class, 'searchQuery']);
