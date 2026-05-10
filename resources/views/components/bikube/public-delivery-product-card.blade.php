@props([
    'title',
    'subtitle' => null,
    'price',
    'oldPrice' => null,
    'badge' => null,
    'image',
    'checkoutUrl',
])

<article class="delivery-product-card">
    @if($badge)
        <span class="delivery-product-card__badge">{{ $badge }}</span>
    @endif
    <img src="{{ $image }}" alt="{{ $title }}" loading="lazy">
    <h3>{{ $title }}</h3>
    @if($subtitle)
        <p>{{ $subtitle }}</p>
    @endif
    <div class="delivery-product-card__price">
        <strong>{{ $price }}</strong>
        @if($oldPrice)
            <span>{{ $oldPrice }}</span>
        @endif
    </div>
    <a href="{{ $checkoutUrl }}">Add to cart</a>
</article>
