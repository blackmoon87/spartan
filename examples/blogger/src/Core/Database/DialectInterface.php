<?php

declare(strict_types=1);

namespace App\Core\Database;

interface DialectInterface
{
    public function quoteIdentifier(string $identifier): string;
    public function quoteTable(string $table): string;
}
