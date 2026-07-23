@extends('layouts.shop')

@section('content')
<h2 style="margin-bottom: 1.5rem;">Order Checkout</h2>

<div style="display: grid; grid-template-columns: 1fr 350px; gap: 2rem;">
    <div class="glass-card">
        <form action="/checkout/process" method="POST">
            @csrf
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; color: var(--text-secondary); font-weight: 500;">Shipping Address</label>
                <textarea name="shipping_address" rows="4" style="width: 100%; padding: 0.85rem; background: rgba(9, 13, 22, 0.8); border: 1px solid var(--border-color); border-radius: 8px; color: #fff; outline: none;" placeholder="Enter complete delivery street address, city, zip code...">123 Tech Innovation Boulevard, Silicon Valley, CA 94025</textarea>
            </div>

            <div style="margin-bottom: 2rem;">
                <label style="display: block; margin-bottom: 0.5rem; color: var(--text-secondary); font-weight: 500;">Payment Method</label>
                <select name="payment_method" style="width: 100%; padding: 0.85rem; background: rgba(9, 13, 22, 0.8); border: 1px solid var(--border-color); border-radius: 8px; color: #fff; outline: none;">
                    <option value="credit_card">Credit / Debit Card (Mock)</option>
                    <option value="apple_pay">Apple Pay</option>
                    <option value="paypal">PayPal Express</option>
                </select>
            </div>

            <button type="submit" class="btn" style="width: 100%; padding: 0.9rem; font-size: 1rem;">Complete & Pay ${{ number_format($total, 2) }}</button>
        </form>
    </div>

    <div class="glass-card">
        <h3 style="margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem;">Order Summary</h3>
        @foreach($items as $entry)
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem; font-size: 0.9rem;">
                <span>{{ $entry['product']->name }} (x{{ $entry['quantity'] }})</span>
                <span style="font-weight: 600;">${{ number_format($entry['subtotal'], 2) }}</span>
            </div>
        @endforeach
        <div style="border-top: 1px solid var(--border-color); padding-top: 0.75rem; margin-top: 1rem; display: flex; justify-content: space-between; font-weight: 700; font-size: 1.1rem;">
            <span>Grand Total:</span>
            <span style="color: var(--accent);">${{ number_format($total, 2) }}</span>
        </div>
    </div>
</div>
@endsection
