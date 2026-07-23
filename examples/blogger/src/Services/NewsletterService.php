<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Newsletter;

class NewsletterService
{
    public function subscribe(string $email): Newsletter
    {
        $existing = (new Newsletter())->findInstanceBy('email', $email);
        if ($existing) {
            return $existing;
        }

        $model = new Newsletter();
        $id = $model->create([
            'email'  => $email,
            'status' => 'active',
        ]);

        return $model->findInstance((int)$id);
    }
}
