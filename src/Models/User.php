<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDOException;

class User extends Model
{
    /**
     * Fetch user count from the database safely.
     */
    public function getCount(): int
    {
        try {
            // Gracefully check if table exists first to avoid crashing clean installations
            $stmt = $this->query("SHOW TABLES LIKE 'users'");
            if ($stmt->rowCount() === 0) {
                return 0;
            }

            $stmt = $this->query("SELECT COUNT(*) as count FROM users");
            $result = $stmt->fetch();
            return (int) ($result['count'] ?? 0);
        } catch (PDOException $e) {
            error_log("Failed to fetch user count: " . $e->getMessage());
            return 0;
        }
    }
}
