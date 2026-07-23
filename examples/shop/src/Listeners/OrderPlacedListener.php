<?php

declare(strict_types=1);

namespace App\Listeners;

/**
 * Example Listener — handles the 'order.placed' event.
 *
 * Register in index.php or a boot file:
 *   Application::$app->events->listen('order.placed', OrderPlacedListener::class);
 *
 * Every listener class MUST implement a handle(mixed $payload): void method.
 */
class OrderPlacedListener
{
    public function handle(mixed $payload): void
    {
        // $payload is whatever was passed to Events::dispatch('order.placed', $data)
        // Example: send SMS, update inventory, log the event, etc.

        // error_log('Order placed: ' . json_encode($payload));
    }
}
