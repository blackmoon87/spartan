@extends('layouts.shop')

@section('content')
<h2 style="margin-bottom: 1.5rem;">Your Shopping Cart</h2>

@empty($items)
    <div class="glass-card" style="text-align: center; padding: 3rem;">
        <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">Your shopping cart is currently empty.</p>
        <a href="/catalog" class="btn">Browse Products &rarr;</a>
    </div>
@else
    <div class="glass-card">
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 2rem;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border-color); text-align: left;">
                    <th style="padding: 1rem 0;">Product</th>
                    <th style="padding: 1rem 0;">Price</th>
                    <th style="padding: 1rem 0;">Quantity</th>
                    <th style="padding: 1rem 0; text-align: right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $entry)
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 1rem 0; font-weight: 600;">{{ $entry['product']->name }}</td>
                        <td style="padding: 1rem 0;">${{ number_format($entry['product']->price, 2) }}</td>
                        <td style="padding: 1rem 0;">{{ $entry['quantity'] }}</td>
                        <td style="padding: 1rem 0; text-align: right; color: var(--accent); font-weight: 700;">
                            ${{ number_format($entry['subtotal'], 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 2px solid var(--border-color); padding-top: 1.5rem;">
            <div style="font-size: 1.4rem; font-weight: 800;">
                Total: <span style="color: var(--accent);">${{ number_format($total, 2) }}</span>
            </div>
            <a href="/checkout" class="btn" style="padding: 0.85rem 2rem; font-size: 1rem;">Proceed to Checkout &rarr;</a>
        </div>
    </div>
@endempty
@endsection
