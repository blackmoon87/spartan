<?php

declare(strict_types=1);

use App\Controllers\AdminProductController;
use App\Core\Application;

/** @var Application $app */

// Protected Admin Routes with auth middleware group
$app->router->get('/admin/products', [AdminProductController::class, 'index'], ['auth']);
$app->router->post('/admin/products/store', [AdminProductController::class, 'store'], ['auth']);
