<section class="relative h-screen min-h-[700px] w-full flex items-center justify-center overflow-hidden">
    <!-- Video Background with Masterful Grading -->
    <div class="absolute inset-0 z-0">
        <video autoplay loop muted playsinline class="object-cover w-full h-full opacity-70">
            <source src="https://assets.mixkit.co/videos/preview/mixkit-grilling-meat-on-a-barbecue-42646-large.mp4" type="video/mp4">
        </video>
        <!-- Sophisticated overlays -->
        <div class="absolute inset-0 bg-black/40"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-zinc-950 via-zinc-950/20 to-transparent"></div>
        <div class="absolute inset-0 bg-gradient-to-b from-zinc-950 via-transparent to-transparent"></div>
    </div>

    <!-- Floating Elegant Navigation -->
    <header :class="isScrolled ? 'bg-zinc-950/80 backdrop-blur-xl py-4 shadow-xl' : 'bg-transparent py-8'" 
            class="fixed top-0 w-full z-50 transition-all duration-500 px-6 lg:px-16 flex justify-between items-center">
        <div class="flex items-center gap-2">
            <span class="text-3xl font-serif font-black gold-gradient-text tracking-[0.2em] uppercase">GLF MaT</span>
        </div>
        
        <div class="hidden md:flex items-center gap-12 text-[11px] font-black uppercase tracking-[0.4em] text-zinc-300">
            <a href="#menu" class="hover:text-amber-500 transition-colors relative group">
                Меню
                <span class="absolute -bottom-1 left-0 w-0 h-px bg-amber-500 transition-all group-hover:w-full"></span>
            </a>
            <a href="#atmosphere" class="hover:text-amber-500 transition-colors relative group">
                Атмосфера
                <span class="absolute -bottom-1 left-0 w-0 h-px bg-amber-500 transition-all group-hover:w-full"></span>
            </a>
            <a href="#reviews" class="hover:text-amber-500 transition-colors relative group">
                Отзывы
                <span class="absolute -bottom-1 left-0 w-0 h-px bg-amber-500 transition-all group-hover:w-full"></span>
            </a>
        </div>

        <div class="flex items-center gap-6">
            <button @click="cartOpen = true" class="relative group">
                <svg class="w-6 h-6 text-white group-hover:text-amber-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                <span x-show="cart.length > 0" class="absolute -top-2 -right-2 bg-amber-500 text-zinc-950 text-[10px] font-black w-5 h-5 flex items-center justify-center rounded-full" x-text="cart.length"></span>
            </button>
            <button class="md:hidden text-white"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg></button>
        </div>
    </header>

    <!-- Cinematic Content -->
    <div class="relative z-10 text-center px-4 max-w-6xl mt-20">
        <div class="mb-8 inline-flex items-center gap-3 px-6 py-2 rounded-full border border-white/10 bg-white/5 backdrop-blur-md">
            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 shadow-[0_0_10px_#f59e0b]"></span>
            <span class="text-[10px] font-black uppercase tracking-[0.3em] text-zinc-400">Гастрономия в Нарвике</span>
        </div>
        
        <h1 class="text-6xl md:text-8xl lg:text-9xl font-black mb-10 leading-[0.9] tracking-tighter">
            <span class="block text-white font-serif italic font-light opacity-80 mb-2">Искусство</span>
            <span class="block gold-gradient-text uppercase">вкуса</span>
        </h1>
        
        <p class="text-lg md:text-xl text-zinc-400 mb-14 max-w-2xl mx-auto leading-relaxed font-medium">
            Две великие кухни. Одна страсть к совершенству. <br>
            Погрузитесь в атмосферу подлинного гостеприимства.
        </p>
        
        <div class="flex flex-col sm:flex-row items-center justify-center gap-8">
            <button @click="document.getElementById('menu').scrollIntoView({behavior: 'smooth'})" 
                    class="group relative px-12 py-6 bg-amber-500 text-zinc-950 font-black uppercase tracking-[0.2em] text-xs rounded-full shadow-[0_20px_50px_rgba(245,158,11,0.3)] hover:shadow-[0_20px_70px_rgba(245,158,11,0.5)] transition-all duration-500 hover:scale-110">
                Открыть меню
            </button>
            
            <button @click="document.getElementById('kitchens').scrollIntoView({behavior: 'smooth'})" 
                    class="group flex items-center gap-4 text-white font-black uppercase tracking-[0.2em] text-xs hover:text-amber-500 transition-all duration-300">
                <span>О концепции</span>
                <span class="w-12 h-px bg-white/30 group-hover:w-16 group-hover:bg-amber-500 transition-all"></span>
            </button>
        </div>
    </div>

    <!-- Scroll & Details -->
    <div class="absolute bottom-12 w-full px-6 lg:px-16 flex justify-between items-end">
        <div class="hidden lg:block space-y-4">
            <div class="flex items-center gap-4 group">
                <span class="text-[10px] font-black uppercase tracking-widest text-zinc-600 group-hover:text-amber-500 transition">Instagram</span>
                <div class="w-8 h-px bg-zinc-800"></div>
            </div>
            <div class="flex items-center gap-4 group">
                <span class="text-[10px] font-black uppercase tracking-widest text-zinc-600 group-hover:text-amber-500 transition">Facebook</span>
                <div class="w-8 h-px bg-zinc-800"></div>
            </div>
        </div>

        <div class="flex flex-col items-center gap-4">
            <span class="text-[9px] uppercase tracking-[0.5em] font-black text-zinc-500">Explore</span>
            <div class="w-px h-16 bg-gradient-to-b from-amber-500 to-transparent"></div>
        </div>

        <div class="hidden lg:block text-right">
            <p class="text-[10px] font-black uppercase tracking-widest text-zinc-600 mb-2">Локация</p>
            <p class="text-xs font-bold text-zinc-400">Narvik, Norway</p>
        </div>
    </div>
</section>
