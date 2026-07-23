<?php

declare(strict_types=1);

namespace App\Core\Attributes;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class RequireRole
{
    public array $roles;

    public function __construct(string ...$roles)
    {
        $this->roles = $roles;
    }
}
