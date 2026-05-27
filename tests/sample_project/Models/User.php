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
     * Define hasMany relationship to orders.
     */
    public function orders(): RelationQuery
    {
        return $this->hasMany(Order::class, 'user_id');
    }
}
