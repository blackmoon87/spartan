<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use App\Core\Traits\HasAuthorization;

class User extends Model
{
    use HasAuthorization;

    protected string $table = 'users';
    protected bool $timestamps = true;
}
