<?php

declare(strict_types=1);

namespace Tests\Sample\Events;

class OrderPlacedListener
{
    /**
     * Handle the order.placed event.
     */
    public function handle(mixed $payload): void
    {
        $orderId = $payload['order_id'] ?? 'unknown';
        $total = $payload['total'] ?? '0.00';
        
        // Write to temporary log file to demonstrate execution
        $logPath = dirname(dirname(__DIR__)) . '/storage/logs/events.log';
        if (!is_dir(dirname($logPath))) {
            mkdir(dirname($logPath), 0777, true);
        }
        
        $message = "[" . date('Y-m-d H:i:s') . "] Event order.placed handled: Order ID #{$orderId} with total {$total}.\n";
        file_put_contents($logPath, $message, FILE_APPEND);
    }
}
