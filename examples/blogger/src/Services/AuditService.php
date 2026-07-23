<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;

class AuditService
{
    public function logAction(?int $userId, string $action, ?string $details = null): void
    {
        (new AuditLog())->create([
            'user_id' => $userId,
            'action'  => $action,
            'details' => $details,
        ]);
    }
}
