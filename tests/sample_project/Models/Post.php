<?php

declare(strict_types=1);

namespace Tests\Sample\Models;

use App\Core\Model;
use App\Core\RelationQuery;

class Post extends Model
{
    protected string $table = 'blogger_posts';
    protected bool $timestamps = true;

    /**
     * Post belongs to an author.
     */
    public function author(): RelationQuery
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Post has many comments.
     */
    public function comments(): RelationQuery
    {
        return $this->hasMany(Comment::class, 'post_id');
    }
}
