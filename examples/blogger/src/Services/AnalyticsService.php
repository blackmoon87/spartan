<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PostViewAnalytic;

class AnalyticsService
{
    public function logView(int $postId, string $ipAddress, ?string $userAgent): void
    {
        (new PostViewAnalytic())->create([
            'post_id'    => $postId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent ?: 'Unknown',
        ]);
    }

    public function getSummary(): array
    {
        $totalViews = (new PostViewAnalytic())->table()->count();
        $uniqueIps  = count((new PostViewAnalytic())->table()->select('ip_address')->get());

        return [
            'total_views' => $totalViews,
            'unique_ips'  => $uniqueIps,
        ];
    }
}
