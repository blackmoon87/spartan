<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Services layer — for complex business logic that doesn't belong in Controllers or Models.
 *
 * Rules:
 * - Services must NOT extend Model or Controller.
 * - Services may use Models (inject via constructor or call directly).
 * - Services are registered in the Container for testability.
 * - Controllers use $this->make(ServiceClass::class) to resolve them.
 *
 * Example:
 *   class OrderService {
 *       public function __construct(private Order $order, private User $user) {}
 *
 *       public function placeOrder(array $data): array {
 *           // validate stock, apply coupon, deduct balance, insert order...
 *       }
 *   }
 */

// This file exists only as documentation. Create service classes in this directory.
