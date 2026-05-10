<section id="menu" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-32">
    <div class="text-center mb-12">
        <h2 class="text-sm font-bold tracking-[0.3em] uppercase text-amber-500 mb-4">Меню</h2>
        <h3 class="text-4xl md:text-5xl font-black font-serif text-white">Умная подача вкуса</h3>
    </div>

    <!-- Category Tabs -->
    <div class="flex justify-center mb-12">
        <div class="inline-flex items-center p-1.5 bg-zinc-900 border border-zinc-800 rounded-full">
            <button @click="activeCategory = 'all'" :class="activeCategory === 'all' ? 'bg-zinc-800 text-white shadow-md' : 'text-zinc-400 hover:text-white'" class="px-6 py-2.5 rounded-full text-sm font-bold transition">
                Всё меню
            </button>
            <button @click="activeCategory = 'ukraine'" :class="activeCategory === 'ukraine' ? 'bg-blue-900/50 text-blue-400 border border-blue-800/50 shadow-md' : 'text-zinc-400 hover:text-white'" class="px-6 py-2.5 rounded-full text-sm font-bold transition">
                Украинская
            </button>
            <button @click="activeCategory = 'azerbaijan'" :class="activeCategory === 'azerbaijan' ? 'bg-amber-900/50 text-amber-400 border border-amber-800/50 shadow-md' : 'text-zinc-400 hover:text-white'" class="px-6 py-2.5 rounded-full text-sm font-bold transition">
                Азербайджанская
            </button>
        </div>
    </div>

    <!-- Products Grid (All filtered) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <template x-for="product in filteredProducts" :key="product.id">
            <div class="group bg-zinc-900/50 backdrop-blur border border-zinc-800 rounded-2xl overflow-hidden hover:border-amber-500/30 transition duration-300 flex flex-col h-full relative">
                
                <!-- Badges -->
                <div class="absolute top-4 left-4 z-10 flex gap-2">
                    <template x-if="product.badge">
                        <span class="bg-amber-500 text-zinc-950 text-[10px] font-black uppercase tracking-wider px-2 py-1 rounded" x-text="product.badge"></span>
                    </template>
                </div>

                <!-- Image -->
                <div class="aspect-[4/3] w-full overflow-hidden relative bg-zinc-800">
                    <img :src="product.image" :alt="product.title" class="w-full h-full object-cover group-hover:scale-105 transition duration-500" loading="lazy" />
                </div>
                
                <!-- Content -->
                <div class="p-5 flex flex-col flex-1">
                    <h4 class="text-lg font-bold text-white mb-2 font-serif leading-tight" x-text="product.title"></h4>
                    <p class="text-xs text-zinc-400 mb-4 flex-1 line-clamp-2" x-text="product.description"></p>
                    
                    <div class="flex items-center justify-between mt-auto">
                        <span class="text-xl font-black text-white" x-text="formatPrice(product.price)"></span>
                        <button @click="addToCart(product)" class="bg-zinc-800 hover:bg-amber-500 hover:text-zinc-950 text-white px-4 py-2 text-sm font-bold uppercase tracking-wider rounded-lg transition duration-200">
                            + В корзину
                        </button>
                    </div>
                </div>
            </div>
        </template>
        
        <!-- Empty State Fallback -->
        <template x-if="filteredProducts.length === 0">
            <div class="col-span-full py-20 text-center">
                <svg class="mx-auto h-12 w-12 text-zinc-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                <h3 class="mt-4 text-lg font-bold text-white">В этой категории пока пусто</h3>
                <p class="mt-2 text-zinc-500">Попробуйте выбрать другую кухню.</p>
            </div>
        </template>
    </div>
</section>
