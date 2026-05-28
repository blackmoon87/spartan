<?php

declare(strict_types=1);

namespace Tests\Sample\Policies;

use Tests\Sample\Models\User;

class PostPolicy
{
    /**
     * Determine if the user can update the post.
     */
    public function update(?User $user, $post): bool
    {
        if (!$user) {
            return false;
        }

        // Admin can update any post
        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            return true;
        }

        // Post author can update
        $postUserId = is_object($post) ? ($post->user_id ?? null) : ($post['user_id'] ?? null);
        return $postUserId !== null && (int)$user->id === (int)$postUserId;
    }

    /**
     * Determine if the user can delete the post.
     */
    public function delete(?User $user, $post): bool
    {
        if (!$user) {
            return false;
        }

        // Admin can delete any post
        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            return true;
        }

        // Post author can delete
        $postUserId = is_object($post) ? ($post->user_id ?? null) : ($post['user_id'] ?? null);
        return $postUserId !== null && (int)$user->id === (int)$postUserId;
    }
}
