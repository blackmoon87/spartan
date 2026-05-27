<?php

declare(strict_types=1);

namespace Tests\Sample\Models;

use App\Core\Model;
use App\Core\RelationQuery;

class User extends Model
{
    protected string $table = 'test_users';
    protected bool $timestamps = true;

    /**
     * Define hasMany relationship to posts.
     */
    public function posts(): RelationQuery
    {
        return $this->hasMany(Post::class, 'user_id');
    }
}
