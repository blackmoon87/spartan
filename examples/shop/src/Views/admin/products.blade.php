@extends('layouts.shop')

@section('content')
<h2 style="margin-bottom: 1.5rem;">Admin Product Management</h2>

@role('admin')
<div style="display: grid; grid-template-columns: 1fr 380px; gap: 2rem;">
    <!-- Product List -->
    <div class="glass-card">
        <h3 style="margin-bottom: 1rem;">Existing Products ({{ count($products) }})</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border-color); text-align: left;">
                    <th style="padding: 0.75rem 0;">ID</th>
                    <th style="padding: 0.75rem 0;">Name</th>
                    <th style="padding: 0.75rem 0;">Price</th>
                    <th style="padding: 0.75rem 0;">Stock</th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $prod)
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 0.75rem 0;">#{{ $prod['id'] }}</td>
                        <td style="padding: 0.75rem 0; font-weight: 600;">{{ $prod['name'] }}</td>
                        <td style="padding: 0.75rem 0; color: var(--accent);">${{ number_format($prod['price'], 2) }}</td>
                        <td style="padding: 0.75rem 0;">{{ $prod['stock'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Create Product Form -->
    <div class="glass-card">
        <h3 style="margin-bottom: 1rem;">Add New Product</h3>
        <form action="/admin/products/store" method="POST">
            @csrf
            
            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.4rem; color: var(--text-secondary);">Product Name</label>
                <input type="text" name="name" required style="width: 100%; padding: 0.7rem; background: rgba(9,13,22,0.8); border: 1px solid var(--border-color); border-radius: 8px; color: #fff;">
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.4rem; color: var(--text-secondary);">Category</label>
                <select name="category_id" required style="width: 100%; padding: 0.7rem; background: rgba(9,13,22,0.8); border: 1px solid var(--border-color); border-radius: 8px; color: #fff;">
                    @foreach($categories as $cat)
                        <option value="{{ $cat['id'] }}">{{ $cat['name'] }}</option>
                    @endforeach
                </select>
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.4rem; color: var(--text-secondary);">Price ($)</label>
                <input type="number" step="0.01" name="price" required style="width: 100%; padding: 0.7rem; background: rgba(9,13,22,0.8); border: 1px solid var(--border-color); border-radius: 8px; color: #fff;">
            </div>

            <div style="margin-bottom: 1rem;">
                <label style="display: block; margin-bottom: 0.4rem; color: var(--text-secondary);">Stock Quantity</label>
                <input type="number" name="stock" value="10" required style="width: 100%; padding: 0.7rem; background: rgba(9,13,22,0.8); border: 1px solid var(--border-color); border-radius: 8px; color: #fff;">
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.4rem; color: var(--text-secondary);">Description</label>
                <textarea name="description" rows="3" required style="width: 100%; padding: 0.7rem; background: rgba(9,13,22,0.8); border: 1px solid var(--border-color); border-radius: 8px; color: #fff;"></textarea>
            </div>

            <button type="submit" class="btn" style="width: 100%;">Create Product</button>
        </form>
    </div>
</div>
@endrole
@endsection
