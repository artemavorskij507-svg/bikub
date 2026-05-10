<!-- Sticky Mobile Header / Cart Trigger -->
<div class="md:hidden fixed bottom-6 left-0 right-0 z-40 px-4 pointer-events-none">
    <div class="pointer-events-auto flex items-center justify-between bg-zinc-900/90 backdrop-blur-xl border border-zinc-700/50 rounded-full p-2 shadow-[0_10px_40px_rgba(0,0,0,0.8)]">
        
        <button @click="document.getElementById('menu').scrollIntoView({behavior: 'smooth'})" class="flex-1 flex justify-center py-3 text-sm font-bold uppercase tracking-widest text-white hover:text-amber-500 transition">
            Меню
        </button>
        
        <div class="w-px h-8 bg-zinc-700"></div>
        
        <button @click="cartOpen = true" class="flex-1 flex justify-center items-center gap-3 py-3 text-sm font-bold text-amber-500 hover:text-amber-400 transition relative">
            Корзина
            <span x-show="cart.length > 0" class="absolute top-1 right-8 flex h-5 w-5 items-center justify-center rounded-full bg-amber-500 text-[10px] font-black text-zinc-950 shadow" x-text="cart.length"></span>
        </button>
        
    </div>
</div>
