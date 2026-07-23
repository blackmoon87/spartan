<?php

declare(strict_types=1);

use App\Controllers\CssShowcaseController;
use App\Core\Application;

/** @var Application $app */

$app->router->get('/', [CssShowcaseController::class, 'index']);
$app->router->get('/tailwind', [CssShowcaseController::class, 'tailwind']);
$app->router->get('/openprops', [CssShowcaseController::class, 'openprops']);
$app->router->get('/vanilla', [CssShowcaseController::class, 'vanilla']);
