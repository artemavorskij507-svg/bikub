<!-- Off-canvas Cart & Checkout (Alpine.js Transition) -->
<div x-show="cartOpen" class="relative z-50" aria-labelledby="slide-over-title" role="dialog" aria-modal="true" x-cloak>
    
    <!-- Background Backdrop -->
    <div x-show="cartOpen" 
         x-transition:enter="ease-in-out duration-500" 
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100" 
         x-transition:leave="ease-in-out duration-500" 
         x-transition:leave-start="opacity-100" 
         x-transition:leave-end="opacity-0" 
         class="fixed inset-0 bg-black/80 backdrop-blur-sm transition-opacity" 
         @click="cartOpen = false"></div>

    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute inset-0 overflow-hidden">
            <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                
                <!-- Slide-over Panel -->
                <div x-show="cartOpen" 
                     x-transition:enter="transform transition ease-in-out duration-500 sm:duration-700" 
                     x-transition:enter-start="translate-x-full" 
                     x-transition:enter-end="translate-x-0" 
                     x-transition:leave="transform transition ease-in-out duration-500 sm:duration-700" 
                     x-transition:leave-start="translate-x-0" 
                     x-transition:leave-end="translate-x-full" 
                     class="pointer-events-auto w-screen max-w-md flex flex-col h-full bg-zinc-950 border-l border-zinc-800 shadow-2xl">
                    
                    <!-- Header -->
                    <div class="flex items-center justify-between px-6 py-6 border-b border-zinc-800 bg-zinc-950/90 backdrop-blur z-10">
                        <h2 class="text-xl font-bold font-serif text-white flex items-center gap-2" id="slide-over-title">
                            Ваш заказ <span class="bg-amber-500 text-zinc-950 text-xs px-2 py-0.5 rounded-full font-black" x-text="cart.length"></span>
                        </h2>
                        <button @click="cartOpen = false" type="button" class="rounded-lg p-2 text-zinc-400 hover:text-white hover:bg-zinc-800 transition">
                            <span class="sr-only">Close panel</span>
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <!-- Scrollable Content -->
                    <div class="flex-1 overflow-y-auto p-6 scrollbar-hide">
                        
                        <!-- Empty State -->
                        <template x-if="cart.length === 0">
                            <div class="h-full flex flex-col items-center justify-center text-center">
                                <svg class="w-16 h-16 text-zinc-800 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                <p class="text-lg font-bold text-white mb-2">Корзина пуста</p>
                                <p class="text-zinc-500 text-sm mb-6">Добавьте вкуснейший борщ или сочный плов, чтобы оформить заказ.</p>
                                <button @click="cartOpen = false; document.getElementById('menu').scrollIntoView({behavior: 'smooth'})" class="px-6 py-3 bg-amber-500 text-zinc-950 font-bold uppercase tracking-widest text-xs rounded-full hover:bg-amber-400 transition">Перейти в меню</button>
                            </div>
                        </template>

                        <!-- Cart Items -->
                        <div class="space-y-4 mb-8">
                            <template x-for="item in cart" :key="item.id">
                                <div class="flex gap-4 items-center bg-zinc-900 border border-zinc-800 p-3 rounded-2xl relative group">
                                    <img :src="item.image" class="w-16 h-16 object-cover rounded-xl border border-zinc-800">
                                    <div class="flex-1">
                                        <h4 class="text-sm font-bold text-white leading-tight mb-1" x-text="item.title"></h4>
                                        <p class="text-amber-500 font-bold text-sm" x-text="formatPrice(item.price * item.qty)"></p>
                                    </div>
                                    <div class="flex items-center bg-zinc-950 rounded-lg border border-zinc-800">
                                        <button @click="decreaseQty(item.id)" class="px-2.5 py-1 text-zinc-400 hover:text-white transition">-</button>
                                        <span class="text-xs font-bold text-white w-4 text-center" x-text="item.qty"></span>
                                        <button @click="increaseQty(item.id)" class="px-2.5 py-1 text-zinc-400 hover:text-white transition">+</button>
                                    </div>
                                </div>
                            </template>
                        </div>
                        
                        <!-- Frequently Bought Together (Upsell) -->
                        <template x-if="cart.length > 0">
                            <div class="mb-8">
                                <h3 class="text-xs font-black uppercase tracking-widest text-amber-500 mb-4 border-b border-zinc-800 pb-2">Часто берут вместе</h3>
                                <div class="flex gap-4 overflow-x-auto pb-4 scrollbar-hide">
                                    <!-- Upsell Item 1 -->
                                    <div class="min-w-[140px] bg-zinc-900 border border-zinc-800 p-3 rounded-2xl flex flex-col items-center text-center group hover:border-amber-500/50 transition">
                                        <img src="https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=150&q=80" class="w-16 h-16 object-cover rounded-full mb-3 group-hover:scale-110 transition duration-300">
                                        <h4 class="text-xs font-bold text-white mb-1 line-clamp-1">Домашний морс</h4>
                                        <p class="text-xs text-amber-500 font-bold mb-3">45 kr</p>
                                        <button class="w-full py-1.5 bg-zinc-800 hover:bg-amber-500 hover:text-zinc-950 text-white text-[10px] font-black uppercase tracking-widest rounded-lg transition">Добавить</button>
                                    </div>
                                    <!-- Upsell Item 2 -->
                                    <div class="min-w-[140px] bg-zinc-900 border border-zinc-800 p-3 rounded-2xl flex flex-col items-center text-center group hover:border-amber-500/50 transition">
                                        <img src="https://images.unsplash.com/photo-1506617564039-2f3b650b7010?auto=format&fit=crop&w=150&q=80" class="w-16 h-16 object-cover rounded-full mb-3 group-hover:scale-110 transition duration-300">
                                        <h4 class="text-xs font-bold text-white mb-1 line-clamp-1">Пампушки с чесноком</h4>
                                        <p class="text-xs text-amber-500 font-bold mb-3">30 kr</p>
                                        <button class="w-full py-1.5 bg-zinc-800 hover:bg-amber-500 hover:text-zinc-950 text-white text-[10px] font-black uppercase tracking-widest rounded-lg transition">Добавить</button>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Simple Form for Demo -->
                        <template x-if="cart.length > 0">
                            <div>
                                <h3 class="text-xs font-black uppercase tracking-widest text-zinc-500 mb-4 border-b border-zinc-800 pb-2">Детали доставки</h3>
                                <form class="space-y-4">
                                    <div>
                                        <label class="block text-xs font-bold text-zinc-400 mb-1">Имя</label>
                                        <input type="text" placeholder="Иван Иванов" class="w-full bg-zinc-900 border border-zinc-800 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-amber-500 transition">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-zinc-400 mb-1">Телефон</label>
                                        <input type="tel" placeholder="+47 XX XX XX XX" class="w-full bg-zinc-900 border border-zinc-800 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-amber-500 transition">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-zinc-400 mb-1">Адрес доставки</label>
                                        <input type="text" placeholder="Улица, дом, квартира" class="w-full bg-zinc-900 border border-zinc-800 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-amber-500 transition">
                                    </div>
                                </form>
                            </div>
                        </template>

                    </div>

                    <!-- Footer Checkout -->
                    <template x-if="cart.length > 0">
                        <div class="p-6 border-t border-zinc-800 bg-zinc-950 mt-auto">
                            <div class="flex justify-between items-center mb-6">
                                <span class="text-zinc-400 font-bold">Итого:</span>
                                <span class="text-2xl font-black text-white" x-text="formatPrice(cartTotal)"></span>
                            </div>
                            <button class="w-full bg-amber-500 hover:bg-amber-400 text-zinc-950 font-black uppercase tracking-widest py-4 rounded-xl shadow-[0_0_20px_rgba(245,158,11,0.2)] transition-all hover:scale-[1.02]">
                                Оформить заказ
                            </button>
                        </div>
                    </template>
                </div>
                
            </div>
        </div>
    </div>
</div>
