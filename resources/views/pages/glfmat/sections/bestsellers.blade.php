<section id="bestsellers" class="w-full py-32 bg-zinc-950">
    <div class="max-w-[100rem] mx-auto px-6 lg:px-16">
        <div class="flex flex-col md:flex-row justify-between items-end mb-20 gap-8">
            <div class="max-w-2xl">
                <h2 class="text-xs font-black tracking-[0.4em] uppercase text-amber-500 mb-6">Бестселлеры</h2>
                <h3 class="text-5xl md:text-7xl font-black text-white leading-none tracking-tighter">
                    Выбор наших <br> 
                    <span class="font-serif italic font-light opacity-80">постоянных гостей</span>
                </h3>
            </div>
            <div class="flex items-center gap-6">
                <button @click="document.getElementById('menu').scrollIntoView({behavior: 'smooth'})" 
                        class="text-xs font-black uppercase tracking-[0.3em] text-white hover:text-amber-500 transition-all flex items-center gap-4">
                    <span>Весь каталог</span>
                    <span class="w-12 h-px bg-zinc-800"></span>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <template x-for="product in products.slice(0, 4)" :key="product.id">
                <div class="group relative bg-zinc-900/40 border border-white/5 overflow-hidden transition-all duration-700 hover:border-amber-500/30">
                    <!-- Image Wrapper -->
                    <div class="aspect-[4/5] overflow-hidden relative">
                        <img :src="product.image" :alt="product.title" class="w-full h-full object-cover grayscale group-hover:grayscale-0 group-hover:scale-110 transition duration-1000" loading="lazy" />
                        <div class="absolute inset-0 bg-gradient-to-t from-zinc-950 via-zinc-950/20 to-transparent opacity-80"></div>
                        
                        <!-- Top Badge -->
                        <div class="absolute top-6 left-6 z-10" x-show="product.badge">
                            <span class="bg-amber-500 text-zinc-950 text-[9px] font-black uppercase tracking-[0.2em] px-3 py-1.5 rounded-sm shadow-xl" x-text="product.badge"></span>
                        </div>
                    </div>
                    
                    <!-- Content Overlay -->
                    <div class="absolute inset-0 flex flex-col justify-end p-8 translate-y-8 group-hover:translate-y-0 transition-transform duration-500">
                        <div class="space-y-4">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-px bg-amber-500"></div>
                                <span class="text-[10px] font-black uppercase tracking-widest text-amber-500" x-text="product.category === 'ukraine' ? 'Украина' : 'Азербайджан'"></span>
                            </div>
                            
                            <h4 class="text-2xl font-bold text-white font-serif leading-tight group-hover:text-amber-500 transition-colors" x-text="product.title"></h4>
                            
                            <p class="text-sm text-zinc-400 line-clamp-2 opacity-0 group-hover:opacity-100 transition-opacity duration-700 delay-100" x-text="product.description"></p>
                            
                            <div class="flex items-center justify-between pt-4 border-t border-white/5 opacity-0 group-hover:opacity-100 transition-opacity duration-700 delay-200">
                                <span class="text-xl font-black text-white" x-text="formatPrice(product.price)"></span>
                                <button @click="addToCart(product)" class="w-12 h-12 bg-white text-zinc-950 flex items-center justify-center rounded-full hover:bg-amber-500 transition-all active:scale-95">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</section>
