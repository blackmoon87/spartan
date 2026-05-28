<?php

declare(strict_types=1);

namespace Tests\Sample\Models;

use App\Core\Model;
use App\Core\RelationQuery;

class Comment extends Model
{
    protected string $table = 'comments';
    protected bool $timestamps = true;

    /**
     * Comment belongs to a post.
     */
    public function post(): RelationQuery
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    /**
     * Comment belongs to an author.
     */
    public function author(): RelationQuery
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
