@extends('layouts.shop')

@section('content')
<div class="glass-card" style="margin-bottom: 2rem; text-align: center; padding: 3.5rem 2rem;">
    <h1 style="font-size: 2.8rem; font-weight: 800; margin-bottom: 1rem; background: var(--gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
        Next-Generation Spartan Tech Store
    </h1>
    <p style="font-size: 1.15rem; color: var(--text-secondary); max-width: 650px; margin: 0 auto 2rem;">
        Discover cutting-edge developer gear, ultra-portable laptops, audio monitors, and mechanical gaming gear powered by Spartan MVC framework.
    </p>
    <a href="/catalog" class="btn" style="padding: 0.85rem 2rem; font-size: 1rem;">Explore Full Catalog &rarr;</a>
</div>

<h2 style="margin-bottom: 1rem;">Featured Categories</h2>
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 3rem;">
    @foreach($categories as $category)
        <div class="glass-card" style="padding: 1.25rem;">
            <h3 style="font-size: 1.1rem; margin-bottom: 0.5rem;">{{ $category['name'] }}</h3>
            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1rem;">{{ $category['description'] }}</p>
            <a href="/catalog?category={{ $category['id'] }}" style="color: var(--primary); text-decoration: none; font-size: 0.9rem; font-weight: 600;">View Category &rarr;</a>
        </div>
    @endforeach
</div>

<h2 style="margin-bottom: 1rem;">Featured Products</h2>
<div class="grid-products">
    @foreach($featuredProducts as $product)
        <div class="product-card">
            <div>
                <h3 style="font-size: 1.1rem; margin-bottom: 0.5rem;">{{ $product['name'] }}</h3>
                <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1rem;">{{ $product['description'] }}</p>
            </div>
            <div>
                <div class="price">${{ number_format($product['price'], 2) }}</div>
                <form action="/cart/add" method="POST">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product['id'] }}">
                    <button type="submit" class="btn" style="width: 100%;">Add to Cart</button>
                </form>
            </div>
        </div>
    @endforeach
</div>
@endsection
