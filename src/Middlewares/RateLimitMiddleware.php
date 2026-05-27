<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use App\Core\Cache;

class RateLimitMiddleware extends Middleware
{
    private int $limit;
    private int $window;

    public function __construct(int $limit = 60, int $window = 60)
    {
        $this->limit = $limit;
        $this->window = $window;
    }

    public function execute(Request $request, Response $response): void
    {
        $ip = $request->getIp();
        $path = $request->getPath();
        $key = 'rate_limit:' . md5($ip . ':' . $path);

        $data = Cache::get($key);
        $currentTime = time();

        if ($data === null || !is_array($data) || $currentTime >= $data['reset_at']) {
            $hits = 1;
            $resetAt = $currentTime + $this->window;
        } else {
            $hits = $data['hits'] + 1;
            $resetAt = $data['reset_at'];
        }

        $remainingTtl = max(1, $resetAt - $currentTime);
        Cache::put($key, ['hits' => $hits, 'reset_at' => $resetAt], $remainingTtl);

        $remaining = max(0, $this->limit - $hits);

        $response->setHeader('X-RateLimit-Limit', (string) $this->limit);
        $response->setHeader('X-RateLimit-Remaining', (string) $remaining);
        $response->setHeader('X-RateLimit-Reset', (string) $resetAt);

        if ($hits > $this->limit) {
            $response->setHeader('Retry-After', (string) $remainingTtl);
            $response->setStatusCode(429);

            if ($request->isAjax()) {
                $response->json(['error' => 'Too many requests. Please try again later.'], 429);
            } else {
                $response->setHeader('Content-Type', 'text/html; charset=utf-8');
                $response->setContent('<h1>429 Too Many Requests</h1><p>Too many requests. Please try again later.</p>');
            }
        }
    }
}
