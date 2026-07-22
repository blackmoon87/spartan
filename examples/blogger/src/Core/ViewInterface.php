<?php

declare(strict_types=1);

namespace App\Core;

interface ViewInterface
{
    public function render(string $view, array $params = []): string;
    public function renderViewOnly(string $view, array $params = []): string;
    public function share(string $key, mixed $value): void;
}
