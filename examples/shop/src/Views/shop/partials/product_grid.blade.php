<div class="grid-products">
    @foreach($products as $product)
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
@empty($products)
    <div style="text-align: center; padding: 3rem; color: var(--text-secondary);">
        <p>No products matching your search criteria were found.</p>
    </div>
@endempty
