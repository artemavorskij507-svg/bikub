@props([
    'title',
    'description',
    'image',
    'tone' => 'green',
    'cta' => null,
    'href' => '#',
])

<article class="delivery-promo delivery-promo--{{ $tone }}">
    <div>
        <h3>{{ $title }}</h3>
        <p>{{ $description }}</p>
        @if($cta)
            <a href="{{ $href }}">{{ $cta }}</a>
        @endif
    </div>
    <img src="{{ $image }}" alt="{{ $title }}" loading="lazy">
</article>
