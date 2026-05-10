@props(['slides' => []])

<section
    class="delivery-hero"
    x-data="{ index: 0, total: {{ count($slides) }} }"
    x-init="setInterval(() => index = (index + 1) % total, 5600)"
>
    <header class="delivery-nav">
        <a href="{{ route('public.category', ['slug' => 'delivery']) }}" class="delivery-brand">
            <img src="{{ asset('/images/bikube/delivery/logo-delivery.svg') }}" alt="BiKuBe Delivery">
        </a>
        <nav class="delivery-nav__links" aria-label="Delivery sections">
            <a href="{{ route('checkout.show', ['scenario' => 'delivery.groceries']) }}">Products</a>
            <a href="{{ route('checkout.show', ['scenario' => 'delivery.meals']) }}">Meals</a>
            <a href="{{ route('checkout.show', ['scenario' => 'delivery.bulky']) }}">Bulky</a>
            <a href="#delivery-support">Support</a>
        </nav>
        <div class="delivery-nav__actions">
            <a class="delivery-btn delivery-btn--ghost" href="{{ route('login') }}">Login</a>
            <a class="delivery-btn delivery-btn--primary" href="{{ route('checkout.show', ['scenario' => 'delivery.groceries']) }}">Start request</a>
        </div>
    </header>

    <template x-for="(slide, i) in {{ json_encode($slides, JSON_UNESCAPED_UNICODE) }}" :key="slide.code">
        <article
            x-show="index === i"
            x-transition.opacity.duration.400ms
            class="delivery-slide"
            :style="`background-image: url('${slide.image}')`"
            :aria-label="slide.title"
        >
            <div class="delivery-hero__bg"></div>
        </article>
    </template>

    <div class="delivery-slider-control">
        <span><b x-text="String(index + 1).padStart(2, '0')"></b> / <span x-text="String(total).padStart(2, '0')"></span></span>
        <button type="button" @click="index = (index - 1 + total) % total" aria-label="Previous slide">&lsaquo;</button>
        <button type="button" @click="index = (index + 1) % total" aria-label="Next slide">&rsaquo;</button>
    </div>
</section>
