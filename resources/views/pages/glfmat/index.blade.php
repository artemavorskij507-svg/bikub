@extends('layouts.app')

@section('title', 'GLF MaT — Доставка вкуса в Нарвике')

@section('content')
<div x-data="glfMatStore()" x-init="init()" class="min-h-screen bg-zinc-950 text-white font-sans selection:bg-amber-500 selection:text-black overflow-x-hidden relative pb-20 lg:pb-0">

    <!-- CSS specifically for GLF MaT -->
    <style>
        [x-cloak] { display: none !important; }
        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
        
        .gold-gradient-text {
            background: linear-gradient(135deg, #FDE68A 0%, #D97706 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .glass-panel-dark {
            background: rgba(24, 24, 27, 0.6);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.5);
        }
    </style>

    <!-- UI Components -->
    @include('pages.glfmat.sections.hero')
    @include('pages.glfmat.sections.trust-badges')
    @include('pages.glfmat.sections.two-kitchens')
    @include('pages.glfmat.sections.bestsellers')
    @include('pages.glfmat.sections.offers')
    @include('pages.glfmat.sections.full-menu')
    @include('pages.glfmat.sections.atmosphere')
    @include('pages.glfmat.sections.how-to-order')
    @include('pages.glfmat.sections.reviews')
    @include('pages.glfmat.sections.faq')
    @include('pages.glfmat.sections.footer')
    
    <!-- Floating / Sticky UI -->
    @include('pages.glfmat.partials.mobile-sticky')
    @include('pages.glfmat.partials.offcanvas-cart')

    <!-- Alpine Store Logic -->
    <script>
        function glfMatStore() {
            return {
                cartOpen: false,
                cart: [],
                activeCategory: 'all',
                isScrolled: false,
                
                // Fallback dummy data if DB is empty, simulating DB structure
                products: @json($products ?? []),
                
                init() {
                    window.addEventListener('scroll', () => {
                        this.isScrolled = window.scrollY > 50;
                    });
                    
                    // Populate with premium dummy data if the database doesn't pass products yet
                    if(this.products.length === 0) {
                        this.products = [
                            { id: 1, title: 'Наваристый Борщ с пампушками', description: 'Классический украинский борщ на говяжьем бульоне со сметаной, салом и чесночными пампушками.', price: 120, category: 'ukraine', image: 'https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=600&q=80', badge: 'Хит', spicy: false },
                            { id: 2, title: 'Шах Плов', description: 'Королевский азербайджанский плов в хрустящем лаваше с бараниной, курагой и каштанами.', price: 240, category: 'azerbaijan', image: 'https://images.unsplash.com/photo-1627054234509-f86a074fb376?auto=format&fit=crop&w=600&q=80', badge: 'Выбор шефа', spicy: false },
                            { id: 3, title: 'Люля-кебаб из баранины', description: 'Сочный люля-кебаб на мангале, подается с красным луком, гранатом и соусом наршараб.', price: 180, category: 'azerbaijan', image: 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?auto=format&fit=crop&w=600&q=80', badge: 'Острое', spicy: true },
                            { id: 4, title: 'Домашние Вареники', description: 'С картофелем, грибами и жареным луком. Подаются с густой домашней сметаной.', price: 95, category: 'ukraine', image: 'https://images.unsplash.com/photo-1638202568661-bc9363bc184b?auto=format&fit=crop&w=600&q=80', badge: '', spicy: false },
                            { id: 5, title: 'Котлета по-киевски', description: 'Нежное куриное филе с жидким сливочным маслом и зеленью внутри, в хрустящей панировке.', price: 160, category: 'ukraine', image: 'https://images.unsplash.com/photo-1604908176997-125f25cc6f3d?auto=format&fit=crop&w=600&q=80', badge: 'Новинка', spicy: false },
                            { id: 6, title: 'Кутабы с зеленью и сыром', description: 'Тончайшее тесто с начинкой из шпината, кинзы и сулугуни. (3 шт)', price: 110, category: 'azerbaijan', image: 'https://images.unsplash.com/photo-1628840042765-356cda07504e?auto=format&fit=crop&w=600&q=80', badge: '', spicy: false }
                        ];
                    }
                },
                
                get cartTotal() {
                    return this.cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
                },
                
                get filteredProducts() {
                    if(this.activeCategory === 'all') return this.products;
                    return this.products.filter(p => p.category === this.activeCategory);
                },
                
                addToCart(product) {
                    const existing = this.cart.find(x => x.id === product.id);
                    if (existing) {
                        existing.qty += 1;
                    } else {
                        this.cart.push({ ...product, qty: 1 });
                    }
                    this.cartOpen = true;
                },
                
                increaseQty(id) {
                    const item = this.cart.find(x => x.id === id);
                    if (item) item.qty += 1;
                },
                
                decreaseQty(id) {
                    const item = this.cart.find(x => x.id === id);
                    if (!item) return;
                    item.qty -= 1;
                    if (item.qty <= 0) {
                        this.cart = this.cart.filter(x => x.id !== id);
                        if(this.cart.length === 0) this.cartOpen = false;
                    }
                },
                
                formatPrice(val) {
                    return val + ' kr';
                }
            };
        }
    </script>
</div>
@endsection

