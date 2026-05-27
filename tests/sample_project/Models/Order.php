<?php

declare(strict_types=1);

namespace Tests\Sample\Models;

use App\Core\Model;
use App\Core\RelationQuery;

class Order extends Model
{
    protected string $table = 'test_orders';
    protected bool $timestamps = true;

    /**
     * Define belongsTo relationship to User.
     */
    public function user(): RelationQuery
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
