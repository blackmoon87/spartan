<?php

declare(strict_types=1);

namespace App\Services;

class PaymentService
{
    public function processPayment(float $amount, string $paymentMethod): array
    {
        // Mock payment processing logic
        $transactionId = 'TXN-' . strtoupper(bin2hex(random_bytes(6)));
        
        return [
            'success' => true,
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'processed_at' => date('Y-m-d H:i:s'),
        ];
    }
}
