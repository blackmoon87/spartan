<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use RuntimeException;

class OrderService
{
    public function __construct(
        private InventoryService $inventory,
        private PaymentService $payment
    ) {}

    public function createOrder(int $userId, array $items, string $shippingAddress, string $paymentMethod): Order
    {
        if (empty($items)) {
            throw new RuntimeException("Cannot create an empty order.");
        }

        $orderModel = new Order();
        $createdOrder = null;

        // Atomic Database Transaction using Spartan Model transaction helper
        $orderModel->transaction(function () use ($userId, $items, $shippingAddress, $paymentMethod, &$createdOrder) {
            $totalAmount = 0.0;
            $orderItemsToInsert = [];

            foreach ($items as $item) {
                $productId = (int) $item['product_id'];
                $quantity  = (int) $item['quantity'];

                if (!$this->inventory->checkStock($productId, $quantity)) {
                    throw new RuntimeException("Stock unavailable for product ID {$productId}.");
                }

                $product = (new Product())->findInstance($productId);
                $unitPrice = (float) $product->price;
                $subtotal = $unitPrice * $quantity;
                $totalAmount += $subtotal;

                $orderItemsToInsert[] = [
                    'product_id' => $productId,
                    'quantity'   => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal'   => $subtotal,
                ];

                // Deduct inventory stock
                $this->inventory->deductStock($productId, $quantity);
            }

            // Process Payment
            $paymentResult = $this->payment->processPayment($totalAmount, $paymentMethod);
            if (!$paymentResult['success']) {
                throw new RuntimeException("Payment processing failed.");
            }

            // Insert Order
            $orderNumber = 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);
            $orderId = (new Order())->create([
                'user_id'          => $userId,
                'order_number'     => $orderNumber,
                'total_amount'     => $totalAmount,
                'status'           => 'paid',
                'shipping_address' => $shippingAddress,
            ]);

            // Insert Order Items
            foreach ($orderItemsToInsert as $orderItem) {
                (new OrderItem())->create([
                    'order_id'   => $orderId,
                    'product_id' => $orderItem['product_id'],
                    'quantity'   => $orderItem['quantity'],
                    'unit_price' => $orderItem['unit_price'],
                    'subtotal'   => $orderItem['subtotal'],
                ]);
            }

            $createdOrder = (new Order())->findInstance($orderId);
        });

        return $createdOrder;
    }
}
