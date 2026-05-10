@php
    $groceriesUrl = route('checkout.show', ['scenario' => 'delivery.groceries']);
    $mealsUrl = route('checkout.show', ['scenario' => 'delivery.meals']);
    $bulkyUrl = route('checkout.show', ['scenario' => 'delivery.bulky']);

    $slides = [
        [
            'eyebrow' => 'BiKuBe Delivery',
            'title' => 'Delivery of everything you need',
            'subtitle' => 'Groceries, ready meals and bulky orders with fast checkout, careful packing and live status tracking.',
            'image' => asset('/images/bikube/delivery/slide-groceries.png') . '?v=1',
            'cta' => $groceriesUrl,
            'ctaLabel' => 'Products delivery',
            'code' => 'delivery.groceries',
        ],
        [
            'eyebrow' => 'Ready meals',
            'title' => 'Hot meals from nearby kitchens',
            'subtitle' => 'Restaurant-ready orders, dispatch visibility and clear ETA from kitchen to door.',
            'image' => asset('/images/bikube/delivery/slide-meals.png') . '?v=1',
            'cta' => $mealsUrl,
            'ctaLabel' => 'Ready meals',
            'code' => 'delivery.meals',
        ],
        [
            'eyebrow' => 'Bulky delivery',
            'title' => 'Large orders without the heavy work',
            'subtitle' => 'Furniture, appliances and oversized goods delivered with planned slots and support.',
            'image' => asset('/images/bikube/delivery/slide-bulky.png') . '?v=1',
            'cta' => $bulkyUrl,
            'ctaLabel' => 'Bulky delivery',
            'code' => 'delivery.bulky',
        ],
    ];

    $stores = [
        ['name' => 'MENY', 'tone' => 'red', 'rating' => '4.9', 'eta' => '30 min', 'logo' => asset('/images/bikube/delivery/stores/meny.png')],
        ['name' => 'KIWI', 'tone' => 'green', 'rating' => '4.8', 'eta' => '30 min', 'logo' => asset('/images/bikube/delivery/stores/kiwi.jpg')],
        ['name' => 'REMA 1000', 'tone' => 'blue', 'rating' => '4.8', 'eta' => '35 min', 'logo' => asset('/images/bikube/delivery/stores/rema1000.svg')],
        ['name' => 'Coop Mega', 'tone' => 'cyan', 'rating' => '4.7', 'eta' => '35 min', 'logo' => asset('/images/bikube/delivery/stores/coopmega.svg')],
        ['name' => 'SPAR', 'tone' => 'red', 'rating' => '4.9', 'eta' => '40 min', 'logo' => asset('/images/bikube/delivery/stores/spar.svg')],
        ['name' => 'Joker', 'tone' => 'yellow', 'rating' => '4.7', 'eta' => '40 min', 'logo' => asset('/images/bikube/delivery/stores/joker.svg')],
    ];
@endphp

<style>
    .delivery-page { width: 100%; max-width: 100vw; color: #f8fafc; overflow-x: hidden; }
    .delivery-page *, .delivery-page *::before, .delivery-page *::after { box-sizing: border-box; }
    .delivery-page a { text-decoration: none; }
    .delivery-hero { position: relative; height: clamp(520px, 48vw, 700px); overflow: hidden; border: 0; border-radius: 0; background: #03070b; box-shadow: none; }
    .delivery-hero__bg { position: absolute; inset: 0; background: linear-gradient(180deg, rgba(2,6,23,.06), rgba(2,6,23,.28)); pointer-events: none; }
    .delivery-nav { position: relative; z-index: 5; display: flex; align-items: center; justify-content: space-between; gap: 24px; padding: 22px 28px 0; }
    .delivery-brand img { width: 178px; max-width: 44vw; }
    .delivery-nav__links { display: flex; align-items: center; gap: 26px; font-size: 13px; font-weight: 700; color: #cbd5e1; }
    .delivery-nav__links a { color: #cbd5e1; }
    .delivery-nav__actions { display: flex; gap: 10px; }
    .delivery-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; border-radius: 16px; border: 1px solid rgba(148,163,184,.35); padding: 11px 18px; font-size: 13px; font-weight: 900; letter-spacing: .2px; transition: transform .18s ease, background .18s ease, border-color .18s ease, box-shadow .18s ease, color .18s ease; }
    .delivery-btn:hover { transform: translateY(-2px); }
    .delivery-btn:active { transform: translateY(0); }
    .delivery-btn--primary { border-color: rgba(190,242,100,.9); background: linear-gradient(145deg, #d9ff57 0%, #a3e635 42%, #65a30d 100%); color: #07110a; box-shadow: 0 12px 30px rgba(132,204,22,.38), 0 0 0 1px rgba(217,249,157,.25), inset 0 1px 0 rgba(255,255,255,.54); }
    .delivery-btn--primary:hover { color: #030712; box-shadow: 0 18px 42px rgba(132,204,22,.52), 0 0 24px rgba(163,230,53,.42), inset 0 1px 0 rgba(255,255,255,.6); }
    .delivery-btn--ghost { color: #f8fafc; background: linear-gradient(160deg, rgba(15,23,42,.92), rgba(15,23,42,.64)); box-shadow: inset 0 1px 0 rgba(255,255,255,.15), 0 8px 20px rgba(2,6,23,.35); }
    .delivery-btn--ghost:hover { color: #ecfccb; border-color: rgba(163,230,53,.6); box-shadow: inset 0 1px 0 rgba(255,255,255,.2), 0 12px 26px rgba(0,0,0,.34), 0 0 20px rgba(132,204,22,.2); }
    .delivery-btn--soft { color: #d9f99d; background: linear-gradient(135deg, rgba(132,204,22,.2), rgba(132,204,22,.1)); border-color: rgba(163,230,53,.5); box-shadow: inset 0 1px 0 rgba(255,255,255,.12); }
    .delivery-btn--soft:hover { color: #f7fee7; border-color: rgba(190,242,100,.8); box-shadow: 0 10px 24px rgba(132,204,22,.18), inset 0 1px 0 rgba(255,255,255,.18); }
    .delivery-btn--large { padding: 14px 22px; font-size: 14px; }
    .delivery-slide { position: absolute; inset: 0; z-index: 2; display: block; height: 100%; padding: 0; background-size: cover; background-position: center center; }
    .delivery-slide::after { content: ""; position: absolute; inset: 0; background: linear-gradient(90deg, rgba(2,6,23,.04), rgba(2,6,23,.02) 48%, rgba(2,6,23,.32)); pointer-events: none; }
    .delivery-copy { max-width: 590px; padding-bottom: 32px; }
    .delivery-eyebrow { display: inline-flex; margin: 0 0 18px; border: 1px solid rgba(163,230,53,.35); border-radius: 999px; background: rgba(132,204,22,.10); padding: 7px 12px; color: #bef264; font-size: 12px; font-weight: 900; text-transform: uppercase; }
    .delivery-copy h1 { margin: 0; max-width: 100%; color: #fff; font-size: clamp(44px, 5.6vw, 76px); line-height: .98; font-weight: 1000; letter-spacing: -1px; overflow-wrap: anywhere; }
    .delivery-copy h1::first-line { color: #fff; }
    .delivery-lead { max-width: 500px; margin: 22px 0 0; color: #e2e8f0; font-size: 17px; line-height: 1.65; }
    .delivery-benefits { display: grid; gap: 10px; margin: 24px 0 0; padding: 0; list-style: none; color: #e5e7eb; font-size: 14px; }
    .delivery-benefits li::before { content: ""; display: inline-block; width: 9px; height: 9px; margin-right: 10px; border-radius: 999px; background: #84cc16; box-shadow: 0 0 16px rgba(132,204,22,.8); }
    .delivery-cta-row { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 30px; }
    .delivery-visual { position: relative; min-height: 470px; display: grid; place-items: center; }
    .delivery-glow { position: absolute; width: 520px; height: 520px; border-radius: 999px; background: radial-gradient(circle, rgba(163,230,53,.35), rgba(34,197,94,.18) 42%, transparent 70%); filter: blur(4px); }
    .delivery-main-image { position: relative; z-index: 1; width: min(560px, 100%); max-height: 530px; object-fit: contain; filter: drop-shadow(0 45px 55px rgba(0,0,0,.55)); }
    .delivery-live-card { position: absolute; z-index: 3; top: 29%; left: 8%; min-width: 180px; border: 1px solid rgba(236,252,203,.32); border-radius: 22px; background: rgba(15,23,42,.56); padding: 18px; backdrop-filter: blur(18px); box-shadow: 0 22px 44px rgba(0,0,0,.28); }
    .delivery-live-card span { display: block; color: #d9f99d; font-size: 12px; font-weight: 800; }
    .delivery-live-card strong { display: block; color: #fff; font-size: 34px; line-height: 1; margin-top: 6px; }
    .delivery-live-card small { display: block; color: #cbd5e1; margin-top: 6px; }
    .delivery-live-card img { width: 62px; margin-top: 12px; }
    .delivery-slider-control { position: absolute; right: 28px; bottom: 28px; z-index: 5; display: flex; align-items: center; gap: 10px; color: #e2e8f0; padding: 0; border-radius: 0; background: transparent; backdrop-filter: none; border: 0; box-shadow: none; }
    .delivery-slider-control b { color: #bef264; }
    .delivery-slider-control button { width: 42px; height: 42px; border: 1px solid rgba(163,230,53,.38); border-radius: 999px; background: radial-gradient(circle at 28% 28%, rgba(51,65,85,.95), rgba(15,23,42,.9)); color: #f8fafc; font-size: 24px; line-height: 1; box-shadow: inset 0 1px 0 rgba(255,255,255,.14), 0 8px 18px rgba(2,6,23,.34); transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease, color .18s ease; }
    .delivery-slider-control button:hover { transform: translateY(-2px) scale(1.03); color: #ecfccb; border-color: rgba(190,242,100,.82); box-shadow: 0 12px 24px rgba(132,204,22,.3), 0 0 18px rgba(132,204,22,.24), inset 0 1px 0 rgba(255,255,255,.2); }
    .delivery-categories { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 18px; margin-top: -34px; position: relative; z-index: 8; padding: 0 28px; }
    .delivery-category { position: relative; min-height: 260px; border: 1px solid rgba(217,249,157,.28); border-radius: 24px; display: flex; align-items: flex-end; overflow: hidden; padding: 24px; color: #fff; background-size: cover; background-position: center; box-shadow: 0 28px 60px rgba(0,0,0,.34); transform: translateZ(0); }
    .delivery-category { appearance: none; text-align: left; }
    .delivery-category { transition: transform .24s ease, box-shadow .24s ease, border-color .24s ease; cursor: pointer; }
    .delivery-category:hover { transform: translateY(-4px); box-shadow: 0 34px 72px rgba(0,0,0,.42); border-color: rgba(190,242,100,.55); }
    .delivery-category--active { border-color: rgba(190,242,100,.9); box-shadow: 0 0 0 2px rgba(190,242,100,.35), 0 30px 66px rgba(0,0,0,.4); }
    .delivery-category::before { content: ""; position: absolute; inset: 0; background: linear-gradient(180deg, rgba(2,6,23,0), rgba(2,6,23,.08) 42%, rgba(2,6,23,.62)); }
    .delivery-category::after { content: ""; position: absolute; inset: 0; border-radius: inherit; box-shadow: inset 0 0 0 1px rgba(255,255,255,.08), inset 0 -54px 70px rgba(0,0,0,.24); pointer-events: none; }
    .delivery-category > span { position: relative; z-index: 2; display: block; max-width: 340px; padding: 0; background: transparent; }
    .delivery-category strong { display: block; font-size: 24px; line-height: 1.05; color: #ffffff; text-shadow: 0 6px 20px rgba(0,0,0,.72), 0 2px 10px rgba(0,0,0,.58); }
    .delivery-category span span { display: block; margin-top: 8px; color: #f1f5f9; font-size: 13px; font-weight: 700; text-shadow: 0 4px 14px rgba(0,0,0,.75), 0 1px 8px rgba(0,0,0,.6); }
    .delivery-category--green { background-image: url('/images/bikube/delivery/category-products.png'); }
    .delivery-category--amber { background-image: url('/images/bikube/delivery/category-meals.png'); }
    .delivery-category--violet { background-image: url('/images/bikube/delivery/category-bulky.png'); }
    .delivery-section { margin-top: 28px; }
    .delivery-stats { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); border: 1px solid rgba(148,163,184,.2); border-radius: 24px; background: rgba(2,6,23,.76); overflow: hidden; }
    .delivery-stat { padding: 22px 24px; border-right: 1px solid rgba(148,163,184,.16); }
    .delivery-stat:last-child { border-right: 0; }
    .delivery-stat strong { display: block; color: #fff; font-size: 23px; }
    .delivery-stat span { display: block; color: #94a3b8; font-size: 12px; margin-top: 5px; }
    .delivery-heading { display: flex; align-items: center; justify-content: space-between; gap: 14px; margin-bottom: 16px; }
    .delivery-heading h2 { margin: 0; color: #fff; font-size: 24px; font-weight: 950; }
    .delivery-tabs { display: flex; flex-wrap: wrap; gap: 9px; }
    .delivery-tab { border-radius: 999px; padding: 9px 14px; background: rgba(15,23,42,.8); color: #cbd5e1; font-size: 12px; font-weight: 800; border: 1px solid rgba(148,163,184,.22); transition: all .2s ease; }
    .delivery-tab:hover { border-color: rgba(163,230,53,.45); color: #e2e8f0; }
    .delivery-tab--active { background: #84cc16; color: #07110a; border-color: rgba(190,242,100,.95); box-shadow: 0 6px 16px rgba(132,204,22,.33); }
    .delivery-products-host { position: relative; min-height: 390px; }
    .delivery-products { display: grid; grid-template-columns: repeat(6, minmax(0,1fr)); gap: 14px; align-items: stretch; }
    .delivery-product-card { position: relative; border: 1px solid rgba(148,163,184,.18); border-radius: 20px; background: linear-gradient(180deg, #151d28, #081019); padding: 12px; box-shadow: 0 16px 36px rgba(0,0,0,.22); transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease; display: flex; flex-direction: column; min-height: 370px; }
    .delivery-product-card:hover { transform: translateY(-5px); border-color: rgba(163,230,53,.46); box-shadow: 0 22px 44px rgba(0,0,0,.34), 0 0 20px rgba(132,204,22,.12); }
    .delivery-product-card__badge { position: absolute; top: 10px; left: 10px; z-index: 2; border-radius: 999px; background: #84cc16; color: #07110a; padding: 4px 8px; font-size: 11px; font-weight: 950; }
    .delivery-product-card img { width: 100%; aspect-ratio: 1.18; object-fit: contain; border-radius: 16px; background: radial-gradient(circle at 50% 40%, rgba(255,255,255,.08), rgba(15,23,42,.1)); padding: 10px; }
    .delivery-product-card h3 { margin: 12px 0 0; color: #fff; font-size: 15px; font-weight: 900; }
    .delivery-product-card p { min-height: 28px; margin: 4px 0 0; color: #a8b3c2; font-size: 11px; }
    .delivery-product-card__price { display: flex; align-items: baseline; gap: 8px; margin-top: 10px; }
    .delivery-product-card__price strong { color: #bef264; font-size: 18px; }
    .delivery-product-card__price span { color: #64748b; font-size: 12px; text-decoration: line-through; }
    .delivery-product-card a { margin-top: auto; display: flex; justify-content: center; align-items: center; border-radius: 12px; border: 1px solid rgba(190,242,100,.8); background: linear-gradient(140deg, #bef264 0%, #84cc16 52%, #65a30d 100%); color: #07110a; padding: 10px 12px; font-size: 12px; font-weight: 950; box-shadow: 0 10px 18px rgba(132,204,22,.28), inset 0 1px 0 rgba(255,255,255,.45); transition: transform .16s ease, box-shadow .16s ease; }
    .delivery-product-card a:hover { transform: translateY(-1px); box-shadow: 0 14px 24px rgba(132,204,22,.34), 0 0 14px rgba(132,204,22,.22), inset 0 1px 0 rgba(255,255,255,.55); }
    .delivery-promos-host { position: relative; min-height: 182px; }
    .delivery-promos { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 16px; }
    .delivery-promo-image { position: relative; min-height: 182px; border-radius: 24px; overflow: hidden; border: 1px solid rgba(148,163,184,.24); display: block; background-size: cover; background-position: center; box-shadow: 0 18px 42px rgba(0,0,0,.34); transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease; }
    .delivery-promo-image:hover { transform: translateY(-4px); border-color: rgba(163,230,53,.5); box-shadow: 0 24px 48px rgba(0,0,0,.38), 0 0 18px rgba(132,204,22,.14); }
    .delivery-promo-image::before { content: ""; position: absolute; inset: 0; background: linear-gradient(180deg, rgba(2,6,23,.12) 0%, rgba(2,6,23,.62) 78%); }
    .delivery-promo-image span { position: absolute; left: 14px; bottom: 12px; z-index: 2; display: inline-flex; align-items: center; gap: 8px; border-radius: 999px; padding: 8px 12px; background: rgba(2,6,23,.56); color: #e2e8f0; font-size: 12px; font-weight: 900; border: 1px solid rgba(163,230,53,.35); backdrop-filter: blur(6px); }
    .delivery-promo-image:hover span { color: #ecfccb; border-color: rgba(190,242,100,.8); box-shadow: 0 0 18px rgba(132,204,22,.3); }
    .delivery-promo { min-height: 164px; display: grid; grid-template-columns: 1fr 132px; align-items: center; gap: 14px; overflow: hidden; border-radius: 24px; padding: 20px; border: 1px solid rgba(148,163,184,.2); }
    .delivery-promo h3 { margin: 0; color: #fff; font-size: 21px; font-weight: 950; }
    .delivery-promo p { margin: 7px 0 0; color: #d1d5db; font-size: 13px; }
    .delivery-promo a { display: inline-flex; margin-top: 16px; border-radius: 12px; border: 1px solid rgba(255,255,255,.26); background: linear-gradient(145deg, rgba(255,255,255,.24), rgba(255,255,255,.1)); color: #fff; padding: 10px 13px; font-size: 12px; font-weight: 900; box-shadow: 0 8px 20px rgba(0,0,0,.25), inset 0 1px 0 rgba(255,255,255,.22); transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease; }
    .delivery-promo a:hover { transform: translateY(-1px); border-color: rgba(255,255,255,.44); box-shadow: 0 12px 24px rgba(0,0,0,.28), inset 0 1px 0 rgba(255,255,255,.28); }
    .delivery-promo img { width: 132px; max-height: 122px; object-fit: contain; filter: drop-shadow(0 18px 22px rgba(0,0,0,.28)); }
    .delivery-promo--green { background: linear-gradient(135deg, rgba(22,101,52,.94), rgba(132,204,22,.24)); }
    .delivery-promo--amber { background: linear-gradient(135deg, rgba(120,53,15,.94), rgba(251,146,60,.25)); }
    .delivery-promo--violet { background: linear-gradient(135deg, rgba(46,16,101,.94), rgba(139,92,246,.28)); }
    .delivery-stores { display: grid; grid-template-columns: repeat(6, minmax(0,1fr)); gap: 12px; }
    .delivery-store { border-radius: 17px; min-height: 92px; padding: 12px; color: #fff; border: 1px solid rgba(255,255,255,.12); background: #111827; display: grid; grid-template-rows: 1fr auto; gap: 8px; }
    .delivery-store__logo-wrap { background: rgba(255,255,255,.94); border-radius: 10px; min-height: 44px; display: grid; place-items: center; padding: 6px; }
    .delivery-store__logo-wrap img { max-width: 100%; max-height: 34px; object-fit: contain; }
    .delivery-store span { display: block; margin-top: 0; color: rgba(255,255,255,.9); font-size: 11px; text-align: center; }
    .delivery-store--green { background: linear-gradient(135deg,#14532d,#16a34a); }
    .delivery-store--red { background: linear-gradient(135deg,#7f1d1d,#dc2626); }
    .delivery-store--blue { background: linear-gradient(135deg,#172554,#2563eb); }
    .delivery-store--cyan { background: linear-gradient(135deg,#164e63,#0891b2); }
    .delivery-store--yellow { background: linear-gradient(135deg,#713f12,#ca8a04); }
    .delivery-benefit-strip { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 12px; border: 1px solid rgba(148,163,184,.18); border-radius: 22px; background: rgba(2,6,23,.78); padding: 14px; }
    .delivery-benefit-strip div { border-right: 1px solid rgba(148,163,184,.12); color: #e2e8f0; font-size: 13px; font-weight: 800; text-align: center; }
    .delivery-benefit-strip div:last-child { border-right: 0; }
    @keyframes deliveryPulse { 0%,100%{ box-shadow:0 0 0 rgba(132,204,22,0);} 50% { box-shadow:0 0 24px rgba(132,204,22,.25);} }
    .delivery-category--active span { animation: deliveryPulse 2.2s ease-in-out infinite; border-radius: 12px; }
    @media (max-width: 1100px) { .delivery-categories, .delivery-products, .delivery-stores { grid-template-columns: repeat(3, minmax(0,1fr)); } .delivery-promos { grid-template-columns: 1fr; } }
    @media (max-width: 760px) { .delivery-hero { width: 100%; max-width: 100%; height: clamp(430px, 126vw, 560px); border-radius: 0; margin: 0; overflow: hidden; } .delivery-slide { background-size: cover; background-position: left center; } .delivery-nav { align-items: flex-start; padding: 18px 16px 0; } .delivery-brand img { width: 132px; } .delivery-nav__links { display: none; } .delivery-nav__actions { margin-left: auto; } .delivery-btn--ghost, .delivery-nav__actions .delivery-btn--primary { display: none; } .delivery-slider-control { right: 16px; bottom: 20px; } .delivery-categories { grid-template-columns: minmax(0, 1fr); margin-top: 18px; padding: 0; } .delivery-category { min-height: 230px; min-width: 0; padding: 18px; background-position: center; } .delivery-stats, .delivery-stores, .delivery-benefit-strip { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); } .delivery-products { grid-template-columns: minmax(0, 1fr); } .delivery-products-host { min-height: 0; } .delivery-product-card { min-width: 0; min-height: 340px; } .delivery-promos-host { min-height: 0; } .delivery-section-head { align-items: flex-start; min-width: 0; } .delivery-product-tabs { max-width: 100%; overflow-x: auto; flex-wrap: wrap; padding-bottom: 4px; } }
    @media (max-width: 440px) { .delivery-hero { height: 480px; } .delivery-slide { background-position: left center; } .delivery-category { min-height: 210px; } .delivery-stats, .delivery-products, .delivery-stores, .delivery-benefit-strip { grid-template-columns: 1fr; } .delivery-promos { grid-template-columns: 1fr; } .delivery-promo { grid-template-columns: 1fr 96px; } }
</style>

<section class="delivery-page" x-data="{ activeSegment: 'products' }">
    <x-bikube.public-delivery-slider :slides="$slides" />

    <div class="delivery-categories">
        <button type="button" @click="activeSegment='products'" :class="{ 'delivery-category--active': activeSegment==='products' }" class="delivery-category delivery-category--green">
            <span><strong>Products Delivery</strong><span>Groceries from Norwegian stores</span></span>
        </button>
        <button type="button" @click="activeSegment='meals'" :class="{ 'delivery-category--active': activeSegment==='meals' }" class="delivery-category delivery-category--amber">
            <span><strong>Ready Meals</strong><span>Hot food from restaurants</span></span>
        </button>
        <button type="button" @click="activeSegment='bulky'" :class="{ 'delivery-category--active': activeSegment==='bulky' }" class="delivery-category delivery-category--violet">
            <span><strong>Bulky Delivery</strong><span>Large goods and home items</span></span>
        </button>
    </div>

    <section class="delivery-section delivery-stats">
        <div class="delivery-stat"><strong>10 000+</strong><span>items available</span></div>
        <div class="delivery-stat"><strong>200+</strong><span>stores and partners</span></div>
        <div class="delivery-stat"><strong>30 min</strong><span>average dispatch</span></div>
        <div class="delivery-stat"><strong>4.9</strong><span>service rating</span></div>
    </section>

    <section id="popular-products" class="delivery-section">
        <div class="delivery-heading">
            <h2>Popular products</h2>
            <div class="delivery-tabs">
                <button type="button" @click="activeSegment='products'" class="delivery-tab" :class="{ 'delivery-tab--active': activeSegment==='products' }">Products</button>
                <button type="button" @click="activeSegment='meals'" class="delivery-tab" :class="{ 'delivery-tab--active': activeSegment==='meals' }">Ready meals</button>
                <button type="button" @click="activeSegment='bulky'" class="delivery-tab" :class="{ 'delivery-tab--active': activeSegment==='bulky' }">Bulky</button>
            </div>
        </div>
        <div class="delivery-products-host">
            <div class="delivery-products" x-show="activeSegment==='products'" x-transition.opacity.duration.200ms>
                    <x-bikube.public-delivery-product-card title="Bananas" subtitle="1 kg" price="129 NOK" old-price="152 NOK" badge="-15%" :image="asset('/images/bikube/delivery/product-bananas.svg')" :checkout-url="$groceriesUrl" />
                    <x-bikube.public-delivery-product-card title="Avocado Hass" subtitle="2 pcs" price="189 NOK" old-price="210 NOK" badge="-10%" :image="asset('/images/bikube/delivery/product-avocado.svg')" :checkout-url="$groceriesUrl" />
                    <x-bikube.public-delivery-product-card title="Parmalat Milk" subtitle="1.5 l" price="89 NOK" old-price="97 NOK" badge="Hit" :image="asset('/images/bikube/delivery/product-milk.svg')" :checkout-url="$groceriesUrl" />
                    <x-bikube.public-delivery-product-card title="Cherry Tomatoes" subtitle="250 g" price="119 NOK" badge="+6%" :image="asset('/images/bikube/delivery/product-tomatoes.svg')" :checkout-url="$groceriesUrl" />
                    <x-bikube.public-delivery-product-card title="Greek Yogurt" subtitle="500 g" price="99 NOK" badge="Fresh" :image="asset('/images/bikube/delivery/product-milk.svg')" :checkout-url="$groceriesUrl" />
                    <x-bikube.public-delivery-product-card title="Lay's Chips" subtitle="150 g" price="129 NOK" old-price="147 NOK" badge="-12%" :image="asset('/images/bikube/delivery/product-chips.svg')" :checkout-url="$groceriesUrl" />
            </div>
            <div class="delivery-products" x-show="activeSegment==='meals'" x-transition.opacity.duration.200ms>
                    <x-bikube.public-delivery-product-card title="Salmon Bowl" subtitle="Ready hot meal" price="229 NOK" badge="Chef choice" :image="asset('/images/bikube/delivery/product-salmon.svg')" :checkout-url="$mealsUrl" />
                    <x-bikube.public-delivery-product-card title="Sushi Set" subtitle="18 pcs" price="289 NOK" badge="Popular" :image="asset('/images/bikube/delivery/product-tomatoes.svg')" :checkout-url="$mealsUrl" />
                    <x-bikube.public-delivery-product-card title="Pasta Carbonara" subtitle="Portion" price="189 NOK" badge="Hot" :image="asset('/images/bikube/delivery/product-milk.svg')" :checkout-url="$mealsUrl" />
                    <x-bikube.public-delivery-product-card title="Chicken Grill" subtitle="Restaurant meal" price="249 NOK" badge="Hit" :image="asset('/images/bikube/delivery/product-avocado.svg')" :checkout-url="$mealsUrl" />
                    <x-bikube.public-delivery-product-card title="Tom Yum Soup" subtitle="500 ml" price="169 NOK" badge="New" :image="asset('/images/bikube/delivery/product-chips.svg')" :checkout-url="$mealsUrl" />
                    <x-bikube.public-delivery-product-card title="Burger Combo" subtitle="Meal set" price="199 NOK" badge="Fast" :image="asset('/images/bikube/delivery/product-bananas.svg')" :checkout-url="$mealsUrl" />
            </div>
            <div class="delivery-products" x-show="activeSegment==='bulky'" x-transition.opacity.duration.200ms>
                    <x-bikube.public-delivery-product-card title="Sofa Delivery" subtitle="2-person team" price="690 NOK" badge="Bulky" :image="asset('/images/bikube/delivery/product-salmon.svg')" :checkout-url="$bulkyUrl" />
                    <x-bikube.public-delivery-product-card title="Washing Machine" subtitle="Lift + install" price="790 NOK" badge="Service" :image="asset('/images/bikube/delivery/product-milk.svg')" :checkout-url="$bulkyUrl" />
                    <x-bikube.public-delivery-product-card title="Bed Frame" subtitle="With carry-in" price="620 NOK" badge="Home" :image="asset('/images/bikube/delivery/product-avocado.svg')" :checkout-url="$bulkyUrl" />
                    <x-bikube.public-delivery-product-card title="TV 65\"" subtitle="Fragile handling" price="540 NOK" badge="Care" :image="asset('/images/bikube/delivery/product-chips.svg')" :checkout-url="$bulkyUrl" />
                    <x-bikube.public-delivery-product-card title="Moving Boxes" subtitle="Bulk transport" price="460 NOK" badge="Move" :image="asset('/images/bikube/delivery/product-bananas.svg')" :checkout-url="$bulkyUrl" />
                    <x-bikube.public-delivery-product-card title="Office Chair" subtitle="Assembled pickup" price="390 NOK" badge="Express" :image="asset('/images/bikube/delivery/product-tomatoes.svg')" :checkout-url="$bulkyUrl" />
            </div>
        </div>
    </section>

    <section class="delivery-section">
        <div class="delivery-promos-host">
            <div class="delivery-promos" x-show="activeSegment==='products'" x-transition.opacity.duration.200ms>
                <a class="delivery-promo-image" href="{{ $groceriesUrl }}" style="background-image: url('{{ asset('/images/bikube/delivery/promo-baner2.png') }}');"><span>Products delivery</span></a>
                <a class="delivery-promo-image" href="{{ $mealsUrl }}" style="background-image: url('{{ asset('/images/bikube/delivery/promo-baner4.png') }}');"><span>Ready meals</span></a>
                <a class="delivery-promo-image" href="{{ $bulkyUrl }}" style="background-image: url('{{ asset('/images/bikube/delivery/promo-baner6.png') }}');"><span>Bulky delivery</span></a>
            </div>
            <div class="delivery-promos" x-show="activeSegment==='meals'" x-transition.opacity.duration.200ms>
                <a class="delivery-promo-image" href="{{ $mealsUrl }}" style="background-image: url('{{ asset('/images/bikube/delivery/promo-baner4.png') }}');"><span>Ready meals</span></a>
                <a class="delivery-promo-image" href="{{ $groceriesUrl }}" style="background-image: url('{{ asset('/images/bikube/delivery/promo-baner2.png') }}');"><span>Products delivery</span></a>
                <a class="delivery-promo-image" href="{{ $bulkyUrl }}" style="background-image: url('{{ asset('/images/bikube/delivery/promo-baner6.png') }}');"><span>Bulky delivery</span></a>
            </div>
            <div class="delivery-promos" x-show="activeSegment==='bulky'" x-transition.opacity.duration.200ms>
                <a class="delivery-promo-image" href="{{ $bulkyUrl }}" style="background-image: url('{{ asset('/images/bikube/delivery/promo-baner6.png') }}');"><span>Bulky delivery</span></a>
                <a class="delivery-promo-image" href="{{ $mealsUrl }}" style="background-image: url('{{ asset('/images/bikube/delivery/promo-baner4.png') }}');"><span>Ready meals</span></a>
                <a class="delivery-promo-image" href="{{ $groceriesUrl }}" style="background-image: url('{{ asset('/images/bikube/delivery/promo-baner2.png') }}');"><span>Products delivery</span></a>
            </div>
        </div>
    </section>

    <section class="delivery-section">
        <div class="delivery-heading">
            <h2>Stores and partners</h2>
            <a class="delivery-btn delivery-btn--soft" href="{{ $groceriesUrl }}">Start request</a>
        </div>
        <div class="delivery-stores">
            @foreach($stores as $store)
                <article class="delivery-store delivery-store--{{ $store['tone'] }}">
                    <div class="delivery-store__logo-wrap">
                        <img src="{{ $store['logo'] }}" alt="{{ $store['name'] }} logo" loading="lazy">
                    </div>
                    <span>{{ $store['rating'] }} rating · {{ $store['eta'] }}</span>
                </article>
            @endforeach
        </div>
    </section>

    <section id="delivery-support" class="delivery-section delivery-benefit-strip">
        <div>Secure payment</div>
        <div>Careful packing</div>
        <div>Support 24/7</div>
        <div>Bonuses and offers</div>
    </section>
</section>

