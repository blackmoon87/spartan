@extends('layouts.shop')

@section('content')
<div class="glass-card" style="text-align: center; max-width: 600px; margin: 2rem auto; padding: 3rem 2rem;">
    <div style="font-size: 3.5rem; margin-bottom: 1rem;">🎉</div>
    <h2 style="font-size: 2rem; color: var(--accent); margin-bottom: 0.5rem;">Order Confirmed!</h2>
    <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">
        Thank you for your order. Your reference number is <strong>{{ $order->order_number }}</strong>.
    </p>

    <div style="background: rgba(9, 13, 22, 0.6); padding: 1.5rem; border-radius: 12px; text-align: left; margin-bottom: 2rem; border: 1px solid var(--border-color);">
        <p><strong>Order Status:</strong> <span style="color: var(--accent);">{{ strtoupper($order->status) }}</span></p>
        <p><strong>Total Paid:</strong> ${{ number_format($order->total_amount, 2) }}</p>
        <p><strong>Shipping Address:</strong> {{ $order->shipping_address }}</p>
    </div>

    <a href="/catalog" class="btn">Continue Shopping &rarr;</a>
</div>
@endsection
