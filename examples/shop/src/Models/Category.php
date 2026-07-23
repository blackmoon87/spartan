<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Core\RelationQuery;

class Category extends Model
{
    protected string $table = 'categories';

    public function products(): RelationQuery
    {
        return $this->hasMany(Product::class, foreignKey: 'category_id');
    }
}
