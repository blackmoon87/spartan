@extends('layouts.shop')

@section('content')
<div style="display: flex; gap: 2rem; align-items: center; justify-content: space-between; margin-bottom: 2rem;">
    <h2>Product Catalog</h2>
    <!-- Live HTMX Instant Search Input -->
    <div style="width: 350px;">
        <input type="text" 
               name="query" 
               hx-post="/shop/search" 
               hx-trigger="keyup changed delay:250ms" 
               hx-target="#product-grid-container" 
               hx-include="[name=_csrf]"
               placeholder="🔍 Search products live with HTMX..." 
               style="width: 100%; padding: 0.75rem 1rem; background: rgba(15, 21, 37, 0.7); border: 1px solid var(--border-color); border-radius: 8px; color: #fff; outline: none;">
        <input type="hidden" name="_csrf" value="{{ $_SESSION['_csrf_token'] ?? '' }}">
    </div>
</div>

<div style="display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap;">
    <a href="/catalog" class="btn" style="background: {{ empty($selectedCategory) ? 'var(--gradient)' : 'rgba(255,255,255,0.05)' }}; color: #fff;">All Categories</a>
    @foreach($categories as $cat)
        <a href="/catalog?category={{ $cat['id'] }}" class="btn" style="background: {{ $selectedCategory == $cat['id'] ? 'var(--gradient)' : 'rgba(255,255,255,0.05)' }}; color: #fff;">
            {{ $cat['name'] }}
        </a>
    @endforeach
</div>

<div id="product-grid-container">
    @include('shop.partials.product_grid', ['products' => $products])
</div>
@endsection
