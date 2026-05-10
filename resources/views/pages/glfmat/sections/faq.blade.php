<section id="faq" class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 mb-32">
    <div class="text-center mb-12">
        <h2 class="text-sm font-bold tracking-[0.3em] uppercase text-amber-500 mb-4">Ответы на вопросы</h2>
        <h3 class="text-4xl md:text-5xl font-black font-serif text-white">Частые вопросы</h3>
    </div>

    <div class="space-y-4" x-data="{ active: null }">
        <!-- FAQ 1 -->
        <div class="bg-zinc-900 border border-zinc-800 rounded-2xl overflow-hidden transition-all duration-300">
            <button @click="active = active === 1 ? null : 1" class="w-full flex justify-between items-center p-6 text-left focus:outline-none">
                <span class="text-lg font-bold text-white">Как быстро вы доставляете?</span>
                <svg class="w-5 h-5 text-amber-500 transform transition-transform duration-300" :class="active === 1 ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>
            <div x-show="active === 1" x-collapse>
                <div class="px-6 pb-6 text-zinc-400">
                    Среднее время доставки по Нарвику составляет 30–45 минут с момента подтверждения заказа. Мы используем термосумки, чтобы еда оставалась горячей.
                </div>
            </div>
        </div>

        <!-- FAQ 2 -->
        <div class="bg-zinc-900 border border-zinc-800 rounded-2xl overflow-hidden transition-all duration-300">
            <button @click="active = active === 2 ? null : 2" class="w-full flex justify-between items-center p-6 text-left focus:outline-none">
                <span class="text-lg font-bold text-white">Можно ли заказать блюда из обеих кухонь одновременно?</span>
                <svg class="w-5 h-5 text-amber-500 transform transition-transform duration-300" :class="active === 2 ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>
            <div x-show="active === 2" x-collapse x-cloak>
                <div class="px-6 pb-6 text-zinc-400">
                    Да! В этом и заключается концепция GLF MaT. Вы можете добавить в корзину украинский борщ и азербайджанский кебаб — мы приготовим и доставим всё в одном заказе.
                </div>
            </div>
        </div>

        <!-- FAQ 3 -->
        <div class="bg-zinc-900 border border-zinc-800 rounded-2xl overflow-hidden transition-all duration-300">
            <button @click="active = active === 3 ? null : 3" class="w-full flex justify-between items-center p-6 text-left focus:outline-none">
                <span class="text-lg font-bold text-white">Какая минимальная сумма для бесплатной доставки?</span>
                <svg class="w-5 h-5 text-amber-500 transform transition-transform duration-300" :class="active === 3 ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>
            <div x-show="active === 3" x-collapse x-cloak>
                <div class="px-6 pb-6 text-zinc-400">
                    Доставка бесплатна при заказе от 350 kr. Для заказов на меньшую сумму стоимость доставки рассчитывается автоматически в зависимости от вашего адреса (от 49 kr).
                </div>
            </div>
        </div>
    </div>
</section>
