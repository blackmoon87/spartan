<?php

declare(strict_types=1);

use App\Controllers\CssShowcaseController;
use App\Core\Application;

/** @var Application $app */

$app->router->get('/', [CssShowcaseController::class, 'index']);
$app->router->get('/css/tailwind', [CssShowcaseController::class, 'tailwind']);
$app->router->get('/css/openprops', [CssShowcaseController::class, 'openprops']);
$app->router->get('/css/vanilla', [CssShowcaseController::class, 'vanilla']);
