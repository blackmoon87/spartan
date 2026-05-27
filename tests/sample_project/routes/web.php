<?php

declare(strict_types=1);

use Tests\Sample\Controllers\BloggerController;

/** @var \App\Core\Application $app */

$app->router->get('/', [BloggerController::class, 'index']);
$app->router->post('/user', [BloggerController::class, 'storeUser']);
$app->router->post('/post', [BloggerController::class, 'storePost']);
$app->router->get('/post/{id}', [BloggerController::class, 'show']);
$app->router->put('/post/{id}', [BloggerController::class, 'updatePost']);
$app->router->delete('/post/{id}', [BloggerController::class, 'destroyPost']);
$app->router->post('/post/{id}/comment', [BloggerController::class, 'storeComment']);
$app->router->get('/redirect-test', [BloggerController::class, 'redirectTest']);
$app->router->post('/search/posts', [BloggerController::class, 'searchPosts']);
