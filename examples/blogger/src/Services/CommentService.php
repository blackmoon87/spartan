<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Comment;

class CommentService
{
    public function addComment(int $postId, string $authorName, string $authorEmail, string $content): Comment
    {
        $commentModel = new Comment();
        $id = $commentModel->create([
            'post_id'      => $postId,
            'author_name'  => $authorName,
            'author_email' => $authorEmail,
            'content'      => $content,
            'status'       => 'approved',
        ]);

        return $commentModel->findInstance((int)$id);
    }
}
