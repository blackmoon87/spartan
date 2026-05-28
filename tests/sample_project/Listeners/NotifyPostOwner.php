<?php

declare(strict_types=1);

namespace Tests\Sample\Listeners;

class NotifyPostOwner
{
    /**
     * Handle the comment posted event payload.
     */
    public function handle(array $comment): void
    {
        $logPath = dirname(dirname(dirname(__DIR__))) . '/storage/logs/notifications.log';
        $logDir = dirname($logPath);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $now = date('Y-m-d H:i:s');
        $message = "[{$now}] [Notification System] New comment on post #{$comment['post_id']}: \"{$comment['content']}\" (By User #{$comment['user_id']})\n";
        file_put_contents($logPath, $message, FILE_APPEND);
    }
}
