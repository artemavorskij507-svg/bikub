@extends('layouts.app')

@section('title', 'Р”РѕСЃС‚Р°РІРєР° вЂ” BiKuBe')
@section('meta_description', 'Р”РѕСЃС‚Р°РІРєР° РїСЂРѕРґСѓРєС‚РѕРІ, РіРѕС‚РѕРІС‹С… Р±Р»СЋРґ Рё РєСЂСѓРїРЅРѕРіР°Р±Р°СЂРёС‚РЅС‹С… Р·Р°РєР°Р·РѕРІ.')

@section('meta_head')
<link rel="canonical" href="{{ url()->current() }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="{{ asset('storage/delivery-template/style.css') }}">
@endsection

@section('content')
@php
  $catalog = collect($deliveryCatalog ?? [])->take(6);
  $partners = collect(data_get($deliveryPartners ?? [], 'stores', []))->take(8);
  $highlights = collect($deliveryHighlights ?? [])->take(5);
  $hero = data_get($deliveryMeta ?? [], 'generated_images.hero') ?: asset('storage/delivery-template/images/hero.jpg');
  $promoGreen = data_get($deliveryMeta ?? [], 'generated_images.promo_green') ?: asset('storage/delivery-template/images/hero.jpg');
  $promoPink = data_get($deliveryMeta ?? [], 'generated_images.promo_pink') ?: asset('storage/delivery-template/images/avocado.jpg');
  $promoYellow = data_get($deliveryMeta ?? [], 'generated_images.promo_yellow') ?: asset('storage/delivery-template/images/chips.jpg');
@endphp

<div class="delivery-page">
  <header class="top-nav">
    <div class="container nav-inner">
      <a href="{{ url('/') }}" class="brand">
        <i class="fa-solid fa-bag-shopping"></i>
        <span>Quick<span>Way</span><small>Р”РѕСЃС‚Р°РІРєР° СЃ Р·Р°Р±РѕС‚РѕР№</small></span>
      </a>
      <nav class="nav-links">
        <a class="active" href="#">РњРѕСЃРєРІР°</a>
        <a href="#">Рћ СЃРµСЂРІРёСЃРµ</a>
        <a href="#">Р”РѕСЃС‚Р°РІРєР°</a>
        <a href="#">РњР°РіР°Р·РёРЅС‹</a>
        <a href="#">Р РµСЃС‚РѕСЂР°РЅС‹</a>
        <a href="#">Р”Р»СЏ Р±РёР·РЅРµСЃР°</a>
      </nav>
      <div class="nav-actions">
        @auth
          <a href="{{ route('account.dashboard') }}" class="btn login">РљР°Р±РёРЅРµС‚</a>
        @else
          <a href="{{ route('login') }}" class="btn login">Р’РѕР№С‚Рё</a>
        @endauth
        <a href="{{ route('register') }}" class="btn signup">Р РµРіРёСЃС‚СЂР°С†РёСЏ</a>
      </div>
    </div>
  </header>

  <section class="hero">
    <div class="container">
      <div class="hero-shell">
        <div class="hero-left">
          <h1>Р”РѕСЃС‚Р°РІРєР° РІСЃРµРіРѕ,<br><span>С‡С‚Рѕ РІР°Рј РЅСѓР¶РЅРѕ</span></h1>
          <p class="hero-sub">РџСЂРѕРґСѓРєС‚С‹ РёР· РјР°РіР°Р·РёРЅРѕРІ, РіРѕС‚РѕРІС‹Рµ Р±Р»СЋРґР° Рё РєСЂСѓРїРЅРѕРіР°Р±Р°СЂРёС‚РЅС‹Рµ Р·Р°РєР°Р·С‹ СЃ Р±С‹СЃС‚СЂРѕР№ Рё Р±РµСЂРµР¶РЅРѕР№ РґРѕСЃС‚Р°РІРєРѕР№ РЅР° РґРѕРј.</p>
          <ul class="hero-features">
            <li><i class="fa-solid fa-check"></i> Р‘С‹СЃС‚СЂР°СЏ РґРѕСЃС‚Р°РІРєР° РѕС‚ 30 РјРёРЅСѓС‚</li>
            <li><i class="fa-solid fa-check"></i> Р‘РµСЂРµР¶РЅРѕРµ РѕС‚РЅРѕС€РµРЅРёРµ Рё СЃРІРµР¶РµСЃС‚СЊ</li>
            <li><i class="fa-solid fa-check"></i> РћС‚СЃР»РµР¶РёРІР°РЅРёРµ Р·Р°РєР°Р·Р° РІ СЂРµР°Р»СЊРЅРѕРј РІСЂРµРјРµРЅРё</li>
          </ul>
          <div class="card-strip">
            <a class="strip-card strip-g" href="{{ route('checkout.show', ['scenario' => 'delivery.groceries']) }}"><img src="{{ asset('storage/delivery-template/images/bananas.jpg') }}" alt=""><div><h3>Продукты</h3><p>из магазинов</p></div></a>
            <a class="strip-card strip-o" href="{{ route('checkout.show', ['scenario' => 'delivery.meals']) }}"><img src="{{ asset('storage/delivery-template/images/milk.jpg') }}" alt=""><div><h3>Готовые блюда</h3><p>из ресторанов</p></div></a>
            <a class="strip-card strip-p" href="{{ route('checkout.show', ['scenario' => 'delivery.bulky']) }}"><img src="{{ asset('storage/delivery-template/images/hero.jpg') }}" alt=""><div><h3>Крупногабаритная</h3><p>доставка</p></div></a>
          </div>
        </div>
        <div class="hero-right">
          <img src="{{ $hero }}" alt="" style="display:none">
          <div class="hero-badge">30 min<small>Р’Р°С€ Р·Р°РєР°Р· РІ РїСѓС‚Рё</small></div>
        </div>
      </div>
      <div class="metrics">
        <div class="metric"><i class="fa-solid fa-box-open"></i><div><b>10 000+</b><span>С‚РѕРІР°СЂРѕРІ РІ РЅР°Р»РёС‡РёРё</span></div></div>
        <div class="metric"><i class="fa-solid fa-shop"></i><div><b>200+</b><span>РјР°РіР°Р·РёРЅРѕРІ Рё СЂРµСЃС‚РѕСЂР°РЅРѕРІ</span></div></div>
        <div class="metric"><i class="fa-solid fa-clock"></i><div><b>30 РјРёРЅ</b><span>СЃСЂРµРґРЅРµРµ РІСЂРµРјСЏ РґРѕСЃС‚Р°РІРєРё</span></div></div>
        <div class="metric"><i class="fa-solid fa-star"></i><div><b>4.9</b><span>СЂРµР№С‚РёРЅРі СЃРµСЂРІРёСЃР°</span></div></div>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <div class="section-title"><h2>РџРѕРїСѓР»СЏСЂРЅС‹Рµ С‚РѕРІР°СЂС‹</h2><a href="#" class="ghost-btn">РЎРјРѕС‚СЂРµС‚СЊ РІСЃРµ</a></div>
      <div class="tabs">
        <span class="tab active">РџСЂРѕРґСѓРєС‚С‹</span><span class="tab">Р“РѕС‚РѕРІС‹Рµ Р±Р»СЋРґР°</span><span class="tab">Р”Р»СЏ РґРѕРјР°</span><span class="tab">РљСЂР°СЃРѕС‚Р° Рё Р·РґРѕСЂРѕРІСЊРµ</span><span class="tab">РќР°РїРёС‚РєРё</span><span class="tab">Р”РµС‚СЃРєРёРµ С‚РѕРІР°СЂС‹</span>
      </div>
      <div class="products-row">
        @forelse($catalog as $item)
          @php
            $image = $item['image_url'] ?? asset('images/delivery-generated/basket-blue.svg');
            $title = $item['title'] ?? 'РўРѕРІР°СЂ';
            $subtitle = $item['subtitle'] ?? ($item['store'] ?? 'BiKuBe');
            $price = (float)($item['price'] ?? 0);
            $old = (float)($item['old_price'] ?? 0);
          @endphp
          <article class="product-card">
            <img src="{{ $image }}" alt="{{ $title }}">
            <div class="product-name">{{ $title }}</div>
            <div class="product-meta">{{ $subtitle }}</div>
            <div class="price-row"><span class="price">{{ $price > 0 ? number_format($price,0,'.',' ') : '129' }} в‚Ѕ</span>@if($old>0)<span class="old">{{ number_format($old,0,'.',' ') }} в‚Ѕ</span>@endif</div>
            <button class="buy"><i class="fa-solid fa-basket-shopping"></i> Р’ РєРѕСЂР·РёРЅСѓ</button>
          </article>
        @empty
          <article class="product-card"><img src="{{ asset('storage/delivery-template/images/bananas.jpg') }}" alt=""><div class="product-name">Р‘Р°РЅР°РЅС‹</div><div class="product-meta">1 РєРі</div><div class="price-row"><span class="price">129 в‚Ѕ</span><span class="old">152 в‚Ѕ</span></div><button class="buy">Р’ РєРѕСЂР·РёРЅСѓ</button></article>
          <article class="product-card"><img src="{{ asset('storage/delivery-template/images/avocado.jpg') }}" alt=""><div class="product-name">РђРІРѕРєР°РґРѕ РҐР°СЃСЃ</div><div class="product-meta">2 С€С‚</div><div class="price-row"><span class="price">189 в‚Ѕ</span><span class="old">219 в‚Ѕ</span></div><button class="buy">Р’ РєРѕСЂР·РёРЅСѓ</button></article>
          <article class="product-card"><img src="{{ asset('storage/delivery-template/images/milk.jpg') }}" alt=""><div class="product-name">РњРѕР»РѕРєРѕ</div><div class="product-meta">1.5 Р»</div><div class="price-row"><span class="price">89 в‚Ѕ</span><span class="old">97 в‚Ѕ</span></div><button class="buy">Р’ РєРѕСЂР·РёРЅСѓ</button></article>
          <article class="product-card"><img src="{{ asset('storage/delivery-template/images/chips.jpg') }}" alt=""><div class="product-name">Р§РёРїСЃС‹ Lay's</div><div class="product-meta">150 Рі</div><div class="price-row"><span class="price">129 в‚Ѕ</span><span class="old">147 в‚Ѕ</span></div><button class="buy">Р’ РєРѕСЂР·РёРЅСѓ</button></article>
          <article class="product-card"><img src="{{ asset('storage/delivery-template/images/hero.jpg') }}" alt=""><div class="product-name">РќР°Р±РѕСЂ РѕРІРѕС‰РµР№</div><div class="product-meta">1 РЅР°Р±РѕСЂ</div><div class="price-row"><span class="price">349 в‚Ѕ</span><span class="old">389 в‚Ѕ</span></div><button class="buy">Р’ РєРѕСЂР·РёРЅСѓ</button></article>
          <article class="product-card"><img src="{{ asset('storage/delivery-template/images/bananas.jpg') }}" alt=""><div class="product-name">Р¤СЂСѓРєС‚С‹ РјРёРєСЃ</div><div class="product-meta">900 Рі</div><div class="price-row"><span class="price">259 в‚Ѕ</span><span class="old">299 в‚Ѕ</span></div><button class="buy">Р’ РєРѕСЂР·РёРЅСѓ</button></article>
        @endforelse
      </div>

      <div class="promo-grid">
        <div class="promo pg"><div><h3>РЎРєРёРґРєР° 20%</h3><p>РЅР° РїРµСЂРІС‹Р№ Р·Р°РєР°Р· РІ РїСЂРёР»РѕР¶РµРЅРёРё</p></div><img src="{{ $promoGreen }}" alt=""></div>
        <div class="promo po"><div><h3>Р‘РµСЃРїР»Р°С‚РЅР°СЏ РґРѕСЃС‚Р°РІРєР°</h3><p>РїСЂРё Р·Р°РєР°Р·Рµ РѕС‚ 1500 в‚Ѕ</p></div><img src="{{ $promoYellow }}" alt=""></div>
        <div class="promo pp"><div><h3>РљСЂСѓРїРЅРѕРіР°Р±Р°СЂРёС‚РЅР°СЏ РґРѕСЃС‚Р°РІРєР°</h3><p>РџРѕРґРЅРёРјРµРј, СЃРѕР±РµСЂС‘Рј Рё СѓСЃС‚Р°РЅРѕРІРёРј</p></div><img src="{{ $promoPink }}" alt=""></div>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <div class="section-title"><h2>РњР°РіР°Р·РёРЅС‹</h2></div>
      <div class="store-grid">
        @forelse($partners as $partner)
          <div class="store"><b>{{ $partner['name'] ?? 'РџР°СЂС‚РЅС‘СЂ' }}</b><span>в… {{ number_format((float)($partner['rating'] ?? 4.8),1) }}</span></div>
        @empty
          <div class="store"><b>Р’РєСѓСЃР’РёР»Р»</b><span>в… 4.9</span></div>
          <div class="store"><b>РџРµСЂРµРєСЂС‘СЃС‚РѕРє</b><span>в… 4.8</span></div>
          <div class="store"><b>РџСЏС‚С‘СЂРѕС‡РєР°</b><span>в… 4.7</span></div>
          <div class="store"><b>РђР·Р±СѓРєР° РІРєСѓСЃР°</b><span>в… 4.9</span></div>
          <div class="store"><b>METRO</b><span>в… 4.8</span></div>
          <div class="store"><b>Р›РµРЅС‚Р°</b><span>в… 4.7</span></div>
          <div class="store"><b>РЎР°РјРѕРєР°С‚</b><span>в… 4.9</span></div>
          <div class="store"><b>РћРєРµР№</b><span>в… 4.7</span></div>
        @endforelse
      </div>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <div class="section-title"><h2>РЎРѕР±СЂР°Р»Рё РґР»СЏ РІР°СЃ</h2></div>
      <div class="collect-grid">
        @forelse($highlights as $h)
          <div class="collect"><img src="{{ asset('storage/delivery-template/images/hero.jpg') }}" alt=""><div class="txt"><b>{{ $h['title'] ?? 'РџРѕРґР±РѕСЂРєР°' }}</b><p>{{ $h['description'] ?? 'РЎРїРµС†РёР°Р»СЊРЅРѕ РґР»СЏ РІР°СЃ' }}</p></div></div>
        @empty
          <div class="collect"><img src="{{ asset('storage/delivery-template/images/hero.jpg') }}" alt=""><div class="txt"><b>Р—Р°РІС‚СЂР°Рє</b><p>Р”Р»СЏ РІСЃРµР№ СЃРµРјСЊРё</p></div></div>
          <div class="collect"><img src="{{ asset('storage/delivery-template/images/avocado.jpg') }}" alt=""><div class="txt"><b>РџСЂР°РІРёР»СЊРЅРѕРµ РїРёС‚Р°РЅРёРµ</b><p>РџРѕР»РµР·РЅС‹Рµ РїСЂРѕРґСѓРєС‚С‹</p></div></div>
          <div class="collect"><img src="{{ asset('storage/delivery-template/images/chips.jpg') }}" alt=""><div class="txt"><b>Р”Р»СЏ РїРёРєРЅРёРєР°</b><p>Р’СЃС‘ РЅР° РІС‹С…РѕРґРЅС‹Рµ</p></div></div>
          <div class="collect"><img src="{{ asset('storage/delivery-template/images/milk.jpg') }}" alt=""><div class="txt"><b>Р”Р»СЏ РґРѕРјР°</b><p>РЈР±РѕСЂРєР° Рё Р±С‹С‚</p></div></div>
          <div class="collect"><img src="{{ asset('storage/delivery-template/images/bananas.jpg') }}" alt=""><div class="txt"><b>РЈС…РѕРґ Р·Р° СЃРѕР±РѕР№</b><p>РљСЂР°СЃРѕС‚Р° Рё Р·РґРѕСЂРѕРІСЊРµ</p></div></div>
        @endforelse
      </div>

      <div class="feature-grid">
        <div class="feature"><i class="fa-solid fa-lock"></i><span>Р‘РµР·РѕРїР°СЃРЅР°СЏ РѕРїР»Р°С‚Р°</span></div>
        <div class="feature"><i class="fa-solid fa-box"></i><span>Р‘РµСЂРµР¶РЅР°СЏ СѓРїР°РєРѕРІРєР°</span></div>
        <div class="feature"><i class="fa-solid fa-headset"></i><span>РџРѕРґРґРµСЂР¶РєР° 24/7</span></div>
        <div class="feature"><i class="fa-solid fa-gift"></i><span>Р‘РѕРЅСѓСЃС‹ Рё Р°РєС†РёРё</span></div>
      </div>
    </div>
  </section>

  <footer class="footer">
    <div class="container footer-grid">
      <div><div class="brand" style="font-size:30px"><i class="fa-solid fa-bag-shopping"></i><span>Quick<span>Way</span></span></div><p>РЎРµСЂРІРёСЃ РґРѕСЃС‚Р°РІРєРё РїСЂРѕРґСѓРєС‚РѕРІ, РіРѕС‚РѕРІС‹С… Р±Р»СЋРґ Рё РєСЂСѓРїРЅРѕРіР°Р±Р°СЂРёС‚РЅС‹С… Р·Р°РєР°Р·РѕРІ.</p></div>
      <div><h4>РЎРµСЂРІРёСЃ</h4><ul><li><a href="#">Рћ СЃРµСЂРІРёСЃРµ</a></li><li><a href="#">Р”РѕСЃС‚Р°РІРєР° Рё РѕРїР»Р°С‚Р°</a></li><li><a href="#">Р’РѕР·РІСЂР°С‚</a></li><li><a href="#">РџРѕРјРѕС‰СЊ</a></li></ul></div>
      <div><h4>РљР»РёРµРЅС‚Р°Рј</h4><ul><li><a href="#">РљР°Рє СЃРґРµР»Р°С‚СЊ Р·Р°РєР°Р·</a></li><li><a href="#">Р’РѕРїСЂРѕСЃС‹ Рё РѕС‚РІРµС‚С‹</a></li><li><a href="#">РЎС‚Р°С‚СѓСЃ Р·Р°РєР°Р·Р°</a></li></ul></div>
      <div><h4>РџР°СЂС‚РЅС‘СЂР°Рј</h4><ul><li><a href="#">Р”Р»СЏ РјР°РіР°Р·РёРЅРѕРІ</a></li><li><a href="#">Р”Р»СЏ СЂРµСЃС‚РѕСЂР°РЅРѕРІ</a></li><li><a href="#">Р”Р»СЏ Р±РёР·РЅРµСЃР°</a></li></ul></div>
      <div><h4>РџРѕРґРґРµСЂР¶РєР° 24/7</h4><ul><li><a href="tel:+78001234567">8 (800) 123-45-67</a></li><li><a href="mailto:support@quickway.ru">support@quickway.ru</a></li><li>РњРѕСЃРєРІР°</li></ul></div>
    </div>
    <div class="container footer-bottom"><span>В© {{ date('Y') }} QuickWay. Р’СЃРµ РїСЂР°РІР° Р·Р°С‰РёС‰РµРЅС‹.</span><span>РџРѕР»СЊР·РѕРІР°С‚РµР»СЊСЃРєРѕРµ СЃРѕРіР»Р°С€РµРЅРёРµ В· РџРѕР»РёС‚РёРєР° РєРѕРЅС„РёРґРµРЅС†РёР°Р»СЊРЅРѕСЃС‚Рё</span></div>
  </footer>
</div>

<script src="{{ asset('storage/delivery-template/script.js') }}"></script>
@endsection


