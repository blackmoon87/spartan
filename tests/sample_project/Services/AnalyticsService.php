<?php

declare(strict_types=1);

namespace Tests\Sample\Services;

use App\Core\Database;

class AnalyticsService
{
    /**
     * Get total comments count.
     */
    public function getCommentCount(): int
    {
        $db = Database::getInstance();
        if (!$db) {
            return 0;
        }
        try {
            $stmt = $db->query("SELECT COUNT(*) as count FROM comments");
            $row = $stmt->fetch();
            return (int)($row['count'] ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Get total posts count.
     */
    public function getPostCount(): int
    {
        $db = Database::getInstance();
        if (!$db) {
            return 0;
        }
        try {
            $stmt = $db->query("SELECT COUNT(*) as count FROM posts");
            $row = $stmt->fetch();
            return (int)($row['count'] ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
