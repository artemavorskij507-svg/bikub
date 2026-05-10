@extends('layouts.app')

@section('title', 'BiKuBe Маркетплейс Доставки — Свежие Продукты Нарвика')

@section('content')
<div x-data="marketplaceApp()" x-init="init()" class="min-h-screen bg-white">
    
    <!-- Hero Section with Gradient -->
    <section class="relative bg-gradient-to-br from-emerald-600 to-lime-400 text-white py-20 lg:py-32 overflow-hidden">
        <div class="absolute inset-0 opacity-20">
            <svg class="absolute w-full h-full" viewBox="0 0 1200 600">
                <defs>
                    <pattern id="dots" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse">
                        <circle cx="20" cy="20" r="2" fill="white"></circle>
                    </pattern>
                </defs>
                <rect width="1200" height="600" fill="url(#dots)"></rect>
            </svg>
        </div>
        
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div class="space-y-8 z-10">
                    <div>
                        <h1 class="text-6xl lg:text-7xl font-black leading-tight text-white">BiKuBe Delivery</h1>
                        <p class="text-2xl font-bold text-lime-200 mt-3">Fresh Daily</p>
                    </div>
                    <p class="text-xl text-white text-opacity-90 max-w-lg">Shop from thousands of farm-fresh fruits, vegetables, dairy, and daily essentials at unbeatable prices.</p>
                    <div class="flex gap-4 pt-4">
                        <button @click="scrollTo('categories')" class="px-8 py-4 bg-white text-emerald-700 font-bold text-lg rounded-full hover:shadow-xl hover:scale-105 transition transform">Shop Now</button>
                        <a href="{{ route('public.catalog.index') }}" class="px-8 py-4 bg-white bg-opacity-20 text-white font-bold text-lg rounded-full hover:bg-opacity-40 transition border-2 border-white backdrop-blur">Browse All</a>
                    </div>
                </div>
                <div class="relative h-96 lg:h-96 hidden lg:block">
                    <svg class="w-full h-full" viewBox="0 0 500 500" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="250" cy="250" r="200" fill="rgba(255,255,255,0.1)" opacity="0.3"/>
                        <circle cx="250" cy="250" r="150" fill="rgba(255,255,255,0.05)" opacity="0.2"/>
                        <text x="250" y="250" text-anchor="middle" dy=".3em" font-size="120" fill="white" font-weight="bold" opacity="0.3">🛒</text>
                        <g transform="translate(150, 150)">
                            <text font-size="60" fill="white">🥬</text>
                        </g>
                        <g transform="translate(320, 160)">
                            <text font-size="60" fill="white">🍎</text>
                        </g>
                        <g transform="translate(160, 320)">
                            <text font-size="60" fill="white">🥕</text>
                        </g>
                        <g transform="translate(310, 300)">
                            <text font-size="60" fill="white">🍊</text>
                        </g>
                    </svg>
                </div>
            </div>
        </div>
    </section>

    <!-- Promo Cards -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Card 1: New User Discount -->
            <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-3xl p-8 text-white shadow-xl hover:shadow-2xl hover:scale-105 transition transform">
                <div class="text-sm font-black uppercase tracking-widest opacity-80">🎉 New User</div>
                <h3 class="text-3xl font-black mt-2">Enjoy 10% Off</h3>
                <p class="text-emerald-100 mt-3 text-sm">Sign up today and get instant savings on your first grocery purchase</p>
                <div class="mt-6 flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white bg-opacity-20">→</span>
                </div>
            </div>
            
            <!-- Card 2: Free Delivery -->
            <div class="bg-gradient-to-br from-pink-500 to-pink-600 rounded-3xl p-8 text-white shadow-xl hover:shadow-2xl hover:scale-105 transition transform">
                <div class="text-sm font-black uppercase tracking-widest opacity-80">🚚 Free Delivery</div>
                <h3 class="text-3xl font-black mt-2">On Orders Over $50</h3>
                <p class="text-pink-100 mt-3 text-sm">Stock up on your weekly groceries and save more with zero delivery charges</p>
                <div class="mt-6 flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white bg-opacity-20">→</span>
                </div>
            </div>
            
            <!-- Card 3: Fresh Groceries -->
            <div class="bg-gradient-to-br from-yellow-400 to-yellow-500 rounded-3xl p-8 text-gray-900 shadow-xl hover:shadow-2xl hover:scale-105 transition transform">
                <div class="text-sm font-black uppercase tracking-widest opacity-80">🥬 Fresh Groceries</div>
                <h3 class="text-3xl font-black mt-2">For Your Family</h3>
                <p class="text-gray-800 mt-3 text-sm">We deliver everything you need straight to your door, without hassle</p>
                <div class="mt-6 flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-gray-900 bg-opacity-20">→</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Popular Categories -->
    <section class="bg-gray-50 py-16" id="categories">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-12">
                <h2 class="text-4xl font-black text-gray-900">Popular Categories</h2>
                <button class="bg-black text-white px-6 py-3 rounded-full font-bold hover:bg-gray-800 transition">Show All →</button>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <div class="bg-blue-100 rounded-3xl p-8 text-center hover:shadow-lg transition cursor-pointer group">
                    <div class="text-6xl mb-4 group-hover:scale-110 transition transform">🥬</div>
                    <h3 class="font-bold text-gray-900">Fresh Vegetables</h3>
                    <p class="text-sm text-gray-600 mt-1">23 Product</p>
                </div>
                <div class="bg-red-100 rounded-3xl p-8 text-center hover:shadow-lg transition cursor-pointer group">
                    <div class="text-6xl mb-4 group-hover:scale-110 transition transform">🍓</div>
                    <h3 class="font-bold text-gray-900">Fruits</h3>
                    <p class="text-sm text-gray-600 mt-1">18 Product</p>
                </div>
                <div class="bg-purple-100 rounded-3xl p-8 text-center hover:shadow-lg transition cursor-pointer group">
                    <div class="text-6xl mb-4 group-hover:scale-110 transition transform">🥛</div>
                    <h3 class="font-bold text-gray-900">Dairy & Eggs</h3>
                    <p class="text-sm text-gray-600 mt-1">08 Product</p>
                </div>
                <div class="bg-orange-100 rounded-3xl p-8 text-center hover:shadow-lg transition cursor-pointer group">
                    <div class="text-6xl mb-4 group-hover:scale-110 transition transform">🥐</div>
                    <h3 class="font-bold text-gray-900">Bakery</h3>
                    <p class="text-sm text-gray-600 mt-1">12 Product</p>
                </div>
                <div class="bg-rose-100 rounded-3xl p-8 text-center hover:shadow-lg transition cursor-pointer group">
                    <div class="text-6xl mb-4 group-hover:scale-110 transition transform">🥩</div>
                    <h3 class="font-bold text-gray-900">Meat & Fish</h3>
                    <p class="text-sm text-gray-600 mt-1">09 Product</p>
                </div>
                <div class="bg-yellow-100 rounded-3xl p-8 text-center hover:shadow-lg transition cursor-pointer group">
                    <div class="text-6xl mb-4 group-hover:scale-110 transition transform">🍹</div>
                    <h3 class="font-bold text-gray-900">Beverages</h3>
                    <p class="text-sm text-gray-600 mt-1">19 Product</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Weekly Best Selling Items -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="flex items-center justify-between mb-12">
            <h2 class="text-4xl font-black text-gray-900">Weekly Best Selling items</h2>
            <button class="bg-black text-white px-6 py-3 rounded-full font-bold hover:bg-gray-800 transition">Show All →</button>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
            <template x-for="product in products.slice(0, 5)" :key="product.id">
                <article class="bg-white rounded-2xl shadow-md hover:shadow-xl transition overflow-hidden group border border-gray-100">
                    <div class="relative h-48 bg-gray-100 overflow-hidden">
                        <img :src="product.image_url || 'https://via.placeholder.com/300x200?text='+product.name" :alt="product.name" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                    </div>
                    <div class="p-5 space-y-3">
                        <h3 class="font-bold text-lg text-gray-900 line-clamp-1" x-text="product.name"></h3>
                        <p class="text-xs text-gray-600">Local Farmers</p>
                        <div class="flex items-center justify-between">
                            <div class="text-2xl font-black text-gray-900" x-text="formatPrice(product.price)"></div>
                            <button @click="addToCart(product)" class="w-10 h-10 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition flex items-center justify-center">+</button>
                        </div>
                    </div>
                </article>
            </template>
        </div>
    </section>

    <!-- Today's Fresh Picks -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16" id="products">
        <div class="flex items-center justify-between mb-12">
            <h2 class="text-4xl font-black text-gray-900">Today's Fresh Picks</h2>
            <button class="bg-black text-white px-6 py-3 rounded-full font-bold hover:bg-gray-800 transition">Show All →</button>
        </div>
        
        <div x-show="loading" class="flex items-center justify-center py-20">
            <div class="animate-spin">
                <div class="w-12 h-12 border-4 border-emerald-200 border-t-emerald-600 rounded-full"></div>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4" x-show="!loading">
            <template x-for="product in products.slice(0, 10)" :key="product.id">
                <article class="bg-white rounded-2xl shadow hover:shadow-lg transition overflow-hidden group">
                    <div class="relative h-40 bg-gray-100 overflow-hidden">
                        <img :src="product.image_url || 'https://via.placeholder.com/300x200?text='+product.name" :alt="product.name" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                    </div>
                    <div class="p-4 space-y-2">
                        <h3 class="font-bold text-sm text-gray-900 line-clamp-2" x-text="product.name"></h3>
                        <p class="text-xs text-gray-600">Local Farmers</p>
                        <div class="flex items-center justify-between pt-2 border-t">
                            <div class="text-lg font-black text-gray-900" x-text="formatPrice(product.price)"></div>
                            <button @click="addToCart(product)" class="w-9 h-9 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition flex items-center justify-center text-sm">+</button>
                        </div>
                    </div>
                </article>
            </template>
        </div>
    </section>

    <!-- CTA Section: Ready to Fill Your Cart -->
    <section class="bg-gradient-to-r from-cyan-100 to-blue-100 py-16 my-12 rounded-3xl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div class="space-y-6">
                    <h2 class="text-4xl font-black text-gray-900">Ready To Fill Your Cart With Freshness?</h2>
                    <p class="text-lg text-gray-700">Shop farm-fresh groceries, daily essentials, and exclusive deals delivered straight to your door.</p>
                    <div class="flex gap-4">
                        <button @click="scrollTo('products')" class="px-8 py-4 bg-black text-white font-bold rounded-full hover:bg-gray-800 transition">Start Shopping</button>
                        <a href="{{ route('public.catalog.index') }}" class="px-8 py-4 bg-white text-black font-bold rounded-full border-2 border-black hover:bg-gray-50 transition">Learn More</a>
                    </div>
                </div>
                <div class="text-6xl text-center">🛒</div>
            </div>
        </div>
    </section>

    <!-- Just for You -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="flex items-center justify-between mb-12">
            <h2 class="text-4xl font-black text-gray-900">Just for you</h2>
            <button class="bg-black text-white px-6 py-3 rounded-full font-bold hover:bg-gray-800 transition">Show All →</button>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
            <template x-for="product in products.slice(5, 15)" :key="product.id">
                <article class="bg-white rounded-2xl shadow hover:shadow-lg transition overflow-hidden group">
                    <div class="relative h-40 bg-gray-100 overflow-hidden">
                        <img :src="product.image_url || 'https://via.placeholder.com/300x200?text='+product.name" :alt="product.name" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                    </div>
                    <div class="p-4 space-y-2">
                        <h3 class="font-bold text-sm text-gray-900 line-clamp-2" x-text="product.name"></h3>
                        <p class="text-xs text-gray-600">Local Farmers</p>
                        <div class="flex items-center justify-between pt-2 border-t">
                            <div class="text-lg font-black text-gray-900" x-text="formatPrice(product.price)"></div>
                            <button @click="addToCart(product)" class="w-9 h-9 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition flex items-center justify-center">+</button>
                        </div>
                    </div>
                </article>
            </template>
        </div>
    </section>

    <!-- Final CTA: Delivered Daily -->
    <section class="bg-gradient-to-r from-blue-50 to-cyan-50 py-16 my-12 rounded-3xl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div class="text-6xl text-center">🥬</div>
                <div class="space-y-6">
                    <h2 class="text-4xl font-black text-gray-900">Fresh Fruits & Vegetables. Delivered Daily.</h2>
                    <p class="text-lg text-gray-700">We deliver everything you need straight to your door.</p>
                    <button @click="scrollTo('products')" class="px-8 py-4 bg-emerald-600 text-white font-bold rounded-full hover:bg-emerald-700 transition">Shop Fresh Produce</button>
                </div>
            </div>
        </div>
    </section>

    <!-- Products Grid (Alternative View) -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <aside class="hidden lg:block space-y-6">
                <div class="bg-white rounded-xl shadow p-6 sticky top-20">
                    <h3 class="font-bold text-lg mb-4 text-gray-900">Search & Filter</h3>
                    <div class="relative mb-4">
                        <input x-model="filters.search" @keyup.enter="applyFilters()" type="text" placeholder="Search items..." class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:border-emerald-500 focus:outline-none transition">
                    </div>
                    <div class="space-y-2">
                        <button @click="filters.category = null; applyFilters()" :class="!filters.category ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-700'" class="w-full px-4 py-2 rounded-lg font-semibold transition">All Categories</button>
                        <template x-for="cat in categories" :key="cat.id">
                            <button @click="filters.category = filters.category === cat.id ? null : cat.id; applyFilters()" :class="filters.category === cat.id ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'" class="w-full px-4 py-2 rounded-lg font-semibold transition text-left" x-text="cat.name"></button>
                        </template>
                    </div>
                </div>
            </aside>

            <div class="lg:col-span-3">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-2xl font-bold text-gray-900">All Products</h3>
                    <div class="text-gray-600 font-semibold" x-text="`${products.length} items`"></div>
                </div>
                
                <div x-show="products.length === 0 && !loading" class="col-span-full py-12 text-center bg-gray-50 rounded-2xl">
                    <div class="text-6xl mb-4">🔍</div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">No products found</h3>
                    <p class="text-gray-600">Try adjusting your filters</p>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-3 gap-4" x-show="!loading">
                    <template x-for="product in products" :key="product.id">
                        <article class="bg-white rounded-2xl shadow hover:shadow-lg transition overflow-hidden group">
                            <div class="relative h-48 bg-gray-100 overflow-hidden">
                                <img :src="product.image_url || 'https://via.placeholder.com/300x200?text='+product.name" :alt="product.name" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                            </div>
                            <div class="p-4 space-y-3">
                                <h3 class="font-bold text-sm text-gray-900 line-clamp-2" x-text="product.name"></h3>
                                <p class="text-xs text-gray-600" x-text="product.unit || 'per item'"></p>
                                <div class="flex items-center justify-between pt-2 border-t">
                                    <div class="text-xl font-black text-gray-900" x-text="formatPrice(product.price)"></div>
                                    <button @click="addToCart(product)" class="w-10 h-10 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition flex items-center justify-center font-bold">+</button>
                                </div>
                            </div>
                        </article>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Sticky Cart -->
    <div class="fixed bottom-8 right-8 z-40">
        <button @click="showCart = !showCart" class="relative w-16 h-16 rounded-full bg-emerald-600 text-white shadow-lg hover:bg-emerald-700 hover:scale-110 transition transform flex items-center justify-center text-3xl">
            🛒
            <span x-show="cartCount > 0" x-cloak class="absolute -top-2 -right-2 bg-red-500 text-white text-sm font-bold w-7 h-7 rounded-full flex items-center justify-center" x-text="cartCount"></span>
        </button>
        <div x-show="showCart" x-cloak class="absolute bottom-20 right-0 w-80 bg-white rounded-2xl shadow-2xl p-6 space-y-6 max-h-96 overflow-y-auto">
            <h3 class="text-2xl font-bold text-gray-900">🛒 Your Cart</h3>
            <div class="space-y-3">
                <template x-for="item in cart" :key="item.id">
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex-1">
                            <p class="font-semibold text-sm" x-text="item.name"></p>
                            <p class="text-xs text-gray-600" x-text="`x${item.quantity}`"></p>
                        </div>
                        <button @click="removeFromCart(item.id)" class="text-red-500 hover:text-red-700 font-bold">✕</button>
                    </div>
                </template>
            </div>
            <div x-show="cart.length === 0" class="text-center py-8 text-gray-500">
                <p>Your cart is empty</p>
            </div>
            <div x-show="cart.length > 0" class="space-y-3">
                <button @click="clearCart()" class="w-full py-2 border-2 border-gray-300 text-gray-700 font-bold rounded-lg hover:bg-gray-50 transition">Clear Cart</button>
                @auth
                    <a href="{{ route('public.cart.index') }}" class="w-full block py-3 bg-emerald-600 text-white font-bold rounded-lg text-center hover:bg-emerald-700 transition">Checkout</a>
                @else
                    <a href="{{ route('login') }}" class="w-full block py-3 bg-emerald-600 text-white font-bold rounded-lg text-center hover:bg-emerald-700 transition">Sign In & Order</a>
                @endauth
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-100 py-16 mt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
                <div>
                    <h4 class="font-black text-2xl text-white mb-4">BiKuBe</h4>
                    <p class="text-sm text-gray-400">Skip the long lines and heavy bags with handle the delivery for you.</p>
                </div>
                <div>
                    <h4 class="font-bold text-white mb-4">Main Pages</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="{{ route('public.index') }}" class="text-gray-400 hover:text-white transition">Home</a></li>
                        <li><a href="{{ route('public.catalog.index') }}" class="text-gray-400 hover:text-white transition">About Us</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition">Return Policy</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition">Partnerships</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold text-white mb-4">Help</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="text-gray-400 hover:text-white transition">Help Center</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition">Return Policy</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition">FAQs</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition">Contact Support</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold text-white mb-4">Contact Information</h4>
                    <div class="space-y-2 text-sm">
                        <p class="text-gray-400">📍 Narvik, Norway</p>
                        <p class="text-gray-400">📧 support@bikube.no</p>
                        <p class="text-gray-400">📱 +47 XXX XX XXX</p>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-800 pt-8 text-center text-sm text-gray-400">
                <p>&copy; 2026 BiKuBe Ecosystem. All rights reserved.</p>
            </div>
        </div>
    </footer>

</div>

<script>
function marketplaceApp() {
    const STORAGE_KEY = 'bikube_delivery_cart_v2';
    
    return {
        products: {!! json_encode($featuredProducts ?? collect([])->map(function($p) { 
            return [
                'id' => $p->id ?? 1,
                'name' => $p->name ?? 'Product',
                'price' => $p->price ?? 99,
                'image_url' => $p->image_url ?? null,
                'unit' => 'per kg',
                'stores_count' => rand(1, 5)
            ];
        })->values()) !!},
        
        topStores: {!! json_encode($topStores ?? [
            ['id' => 1, 'name' => 'AMFI', 'city' => 'Narvik'],
            ['id' => 2, 'name' => 'Coop Extra', 'city' => 'Narvik'],
            ['id' => 3, 'name' => 'Bunnpris', 'city' => 'Narvik'],
        ]) !!},
        
        categories: [
            { id: 1, name: '🥬 Fresh Vegetables' },
            { id: 2, name: '🍓 Fruits' },
            { id: 3, name: '🥐 Bakery' },
            { id: 4, name: '🥛 Dairy & Eggs' },
            { id: 5, name: '🥩 Meat & Fish' },
            { id: 6, name: '🍹 Beverages' },
        ],

        cart: [],
        loading: false,
        showCart: false,

        filters: {
            search: '',
            category: null,
        },

        get cartCount() {
            return this.cart.reduce((sum, item) => sum + item.quantity, 0);
        },

        init() {
            this.loadCart();
            // Add sample products if empty
            if (this.products.length === 0) {
                this.products = [
                    { id: 1, name: 'Seedless Green Grapes', price: 133, image_url: '🍇', unit: 'per kg' },
                    { id: 2, name: 'Organic Strawberries', price: 133, image_url: '🍓', unit: 'per kg' },
                    { id: 3, name: 'Imported Kiwi', price: 133, image_url: '🥝', unit: 'per kg' },
                    { id: 4, name: 'Sweet Pomegranates', price: 133, image_url: '🌴', unit: 'per kg' },
                    { id: 5, name: 'Ripe Papaya', price: 133, image_url: '🧆', unit: 'per kg' },
                    { id: 6, name: 'Premium Basmati Rice', price: 133, image_url: '🍚', unit: 'per kg' },
                    { id: 7, name: 'Fresh Pineapple', price: 133, image_url: '🍍', unit: 'per item' },
                    { id: 8, name: 'Ripe Papaya', price: 133, image_url: '🧆', unit: 'per kg' },
                    { id: 9, name: 'Organic Red Tomatoes', price: 133, image_url: '🍅', unit: 'per kg' },
                    { id: 10, name: 'Organic Strawberries', price: 133, image_url: '🍓', unit: 'per kg' },
                ];
            }
            console.log('✓ BiKuBe Marketplace initialized with', this.products.length, 'products');
        },

        applyFilters() {
            console.log('Applying filters:', this.filters);
        },

        scrollTo(elementId) {
            const el = document.getElementById(elementId);
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        },

        addToCart(product) {
            const existing = this.cart.find(item => item.id === product.id);
            if (existing) {
                existing.quantity++;
            } else {
                this.cart.push({ ...product, quantity: 1 });
            }
            this.saveCart();
            console.log('✓ Added to cart:', product.name);
        },

        removeFromCart(productId) {
            this.cart = this.cart.filter(item => item.id !== productId);
            this.saveCart();
        },

        clearCart() {
            if (confirm('Clear your cart?')) {
                this.cart = [];
                this.saveCart();
            }
        },

        saveCart() {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(this.cart));
        },

        loadCart() {
            try {
                const stored = localStorage.getItem(STORAGE_KEY);
                this.cart = stored ? JSON.parse(stored) : [];
            } catch (e) {
                console.warn('Failed to load cart:', e);
                this.cart = [];
            }
        },

        formatPrice(price) {
            if (!price || typeof price !== 'number') return '$ 0.00';
            return '$ ' + price.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        },
    };
}
</script>

@endsection
