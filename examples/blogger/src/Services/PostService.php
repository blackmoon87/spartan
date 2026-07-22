<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Post;
use RuntimeException;

class PostService
{
    public function createPost(int $userId, int $categoryId, string $title, string $excerpt, string $content): Post
    {
        $slug = preg_replace('/[^a-z0-9-]+/', '-', strtolower(trim($title)));
        
        $postModel = new Post();
        $postId = $postModel->create([
            'user_id'     => $userId,
            'category_id' => $categoryId,
            'title'       => $title,
            'slug'        => $slug,
            'excerpt'     => $excerpt,
            'content'     => $content,
            'status'      => 'published',
            'views'       => 0,
            'featured'    => 1,
        ]);

        $createdPost = $postModel->findInstance((int)$postId);
        if (!$createdPost) {
            throw new RuntimeException("Failed to retrieve created post.");
        }

        return $createdPost;
    }

    public function incrementViews(Post $post): void
    {
        $newViews = (int)$post->views + 1;
        $post->table()->where('id', (int)$post->id)->update(['views' => $newViews]);
    }
}
