<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use RuntimeException;

class InventoryService
{
    public function checkStock(int $productId, int $quantity): bool
    {
        $product = (new Product())->findInstance($productId);
        if (!$product) {
            throw new RuntimeException("Product #{$productId} not found.");
        }

        return (int) $product->stock >= $quantity;
    }

    public function deductStock(int $productId, int $quantity): void
    {
        $product = (new Product())->findInstance($productId);
        if (!$product) {
            throw new RuntimeException("Product #{$productId} not found.");
        }

        $newStock = (int) $product->stock - $quantity;
        if ($newStock < 0) {
            throw new RuntimeException("Insufficient stock for product {$product->name}.");
        }

        $product->save($productId, ['stock' => $newStock]);
    }
}
