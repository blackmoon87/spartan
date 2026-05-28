<?php

declare(strict_types=1);

use Tests\Sample\Controllers\BloggerController;
use Tests\Sample\Controllers\AuthController;

/** @var \App\Core\Application $app */

$app->router->get('/', [BloggerController::class, 'index']);
$app->router->post('/user', [BloggerController::class, 'storeUser']);
$app->router->post('/post', [BloggerController::class, 'storePost']);
$app->router->get('/post/{id}', [BloggerController::class, 'show']);
$app->router->get('/blog/{slug}', [BloggerController::class, 'showBySlug']);
$app->router->put('/post/{id}', [BloggerController::class, 'updatePost']);
$app->router->delete('/post/{id}', [BloggerController::class, 'destroyPost']);
$app->router->post('/post/{id}/comment', [BloggerController::class, 'storeComment'], ['rate_limit:3,60']);
$app->router->get('/redirect-test', [BloggerController::class, 'redirectTest']);
$app->router->excludeCsrf('/search/posts');
$app->router->post('/search/posts', [BloggerController::class, 'searchPosts']);

// Authentication Routes
$app->router->get('/login', [AuthController::class, 'login']);
$app->router->post('/login', [AuthController::class, 'postLogin'], ['rate_limit:5,60']);
$app->router->get('/register', [AuthController::class, 'register']);
$app->router->post('/register', [AuthController::class, 'postRegister']);
$app->router->post('/logout', [AuthController::class, 'logout']);
