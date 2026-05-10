<section id="kitchens" class="w-full bg-zinc-950 py-32 overflow-hidden">
    <div class="max-w-[100rem] mx-auto px-6 lg:px-16 mb-24">
        <div class="flex flex-col items-center text-center">
            <h2 class="text-xs font-black tracking-[0.5em] uppercase text-amber-500 mb-8">Две культуры</h2>
            <h3 class="text-5xl md:text-8xl font-black text-white tracking-tighter leading-none mb-12">
                Гармония <br> 
                <span class="font-serif italic font-light opacity-80">севера и юга</span>
            </h3>
            <p class="text-lg text-zinc-400 max-w-3xl leading-relaxed font-medium">
                Мы не просто готовим еду — мы воссоздаем атмосферу двух великих гастрономических традиций. 
                От домашних семейных рецептов до обжигающих ароматов мангала.
            </p>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row items-stretch min-h-[800px] border-y border-white/5">
        <!-- Ukraine Split -->
        <div class="flex-1 relative group overflow-hidden border-r border-white/5">
            <div class="absolute inset-0 z-0">
                <img src="https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=1200&q=80" 
                     alt="Ukraine" 
                     class="w-full h-full object-cover grayscale group-hover:grayscale-0 group-hover:scale-105 transition-all duration-[2000ms]" />
                <div class="absolute inset-0 bg-zinc-950/60 group-hover:bg-zinc-950/20 transition-all duration-700"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-zinc-950 via-transparent to-transparent"></div>
            </div>

            <div class="relative z-10 p-12 lg:p-24 h-full flex flex-col justify-end">
                <div class="mb-8">
                    <span class="inline-block text-[10px] font-black uppercase tracking-[0.4em] text-blue-400 mb-4">Традиции севера</span>
                    <h4 class="text-4xl md:text-6xl font-black text-white font-serif italic mb-8">Украина</h4>
                    <p class="text-zinc-300 max-w-md text-sm leading-relaxed mb-10 opacity-0 group-hover:opacity-100 translate-y-4 group-hover:translate-y-0 transition-all duration-700">
                        Борщ с ароматными пампушками, нежные вареники и та самая котлета по-киевски. Вкус дома в каждом кусочке.
                    </p>
                    <button class="flex items-center gap-4 text-white text-[10px] font-black uppercase tracking-widest group/btn">
                        <span>Исследовать кухню</span>
                        <div class="w-8 h-px bg-white group-hover/btn:w-16 group-hover/btn:bg-blue-400 transition-all"></div>
                    </button>
                </div>
            </div>
        </div>

        <!-- Azerbaijan Split -->
        <div class="flex-1 relative group overflow-hidden">
            <div class="absolute inset-0 z-0">
                <img src="https://images.unsplash.com/photo-1555939594-58d7cb561ad1?auto=format&fit=crop&w=1200&q=80" 
                     alt="Azerbaijan" 
                     class="w-full h-full object-cover grayscale group-hover:grayscale-0 group-hover:scale-105 transition-all duration-[2000ms]" />
                <div class="absolute inset-0 bg-zinc-950/60 group-hover:bg-zinc-950/20 transition-all duration-700"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-zinc-950 via-transparent to-transparent"></div>
            </div>

            <div class="relative z-10 p-12 lg:p-24 h-full flex flex-col justify-end">
                <div class="mb-8">
                    <span class="inline-block text-[10px] font-black uppercase tracking-[0.4em] text-amber-500 mb-4">Огонь Кавказа</span>
                    <h4 class="text-4xl md:text-6xl font-black text-white font-serif italic mb-8">Азербайджан</h4>
                    <p class="text-zinc-300 max-w-md text-sm leading-relaxed mb-10 opacity-0 group-hover:opacity-100 translate-y-4 group-hover:translate-y-0 transition-all duration-700">
                        Обжигающий люля-кебаб, ароматный шах-плов и кутабы прямо с саджа. Энергия огня и пряных специй.
                    </p>
                    <button class="flex items-center gap-4 text-white text-[10px] font-black uppercase tracking-widest group/btn">
                        <span>Исследовать кухню</span>
                        <div class="w-8 h-px bg-white group-hover/btn:w-16 group-hover/btn:bg-amber-500 transition-all"></div>
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>
