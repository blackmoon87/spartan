<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PostLike;

class PostLikeService
{
    public function toggleLike(int $postId, ?int $userId, string $ipAddress): int
    {
        $likeModel = new PostLike();
        $likeModel->create([
            'post_id'    => $postId,
            'user_id'    => $userId,
            'ip_address' => $ipAddress,
        ]);

        return $likeModel->table()->where('post_id', $postId)->count();
    }
}
