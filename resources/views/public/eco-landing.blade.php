@extends('layouts.app')

@section('content')
<div x-data="ecoPage()" x-init="initPage()" class="min-h-screen bg-slate-950 text-white font-sans selection:bg-amber-500 selection:text-black">

    {{-- 1. HERO SECTION: Dark BiKube Theme --}}
    <div class="relative h-[600px] overflow-hidden">
        <div class="absolute inset-0">
            <img src="https://images.unsplash.com/photo-1532996122724-e3c354a0b15b?auto=format&fit=crop&w=1920&q=80" 
                 class="w-full h-full object-cover opacity-40">
            <div class="absolute inset-0 bg-gradient-to-r from-slate-950 via-slate-900/80 to-emerald-900/40"></div>
            <div class="absolute inset-0 opacity-10 pointer-events-none mix-blend-overlay" 
                 style="background-image: url('data:image/svg+xml;utf8,<svg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\"><path d=\"M30 0l25.98 15v30L30 60 4.02 45V15z\" fill-rule=\"evenodd\" stroke=\"%23fbbf24\" fill=\"none\" stroke-width=\"0.5\"/></svg>'); background-size: 60px 60px; background-repeat: repeat;">
            </div>
        </div>
        
        <div class="relative container mx-auto px-4 h-full flex flex-col justify-center items-start z-10">
            <div class="inline-flex items-center space-x-2 mb-6 animate-fade-in-down">
                <span class="bg-amber-500 text-black text-xs font-bold px-3 py-1 rounded-full uppercase tracking-widest flex items-center">
                    <span class="mr-1">♻️</span> BiKube Eco
                </span>
                <span class="border border-emerald-500/50 text-emerald-400 text-xs px-3 py-1 rounded-full uppercase tracking-widest">
                    Certified Recycler
                </span>
            </div>
            
            <h1 class="text-5xl md:text-7xl font-black mb-6 leading-tight drop-shadow-xl">
                Освободите место.<br>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-300 via-amber-500 to-emerald-500">Мы позаботимся о природе.</span>
            </h1>
            
            <p class="text-xl text-emerald-100/90 mb-10 max-w-2xl font-light leading-relaxed">
                Вывоз старой мебели, техники и строительного мусора в Нарвике. Мы сортируем 95% отходов для вторичной переработки.
            </p>
            
            <button @click="scrollToCalculator()" class="bg-amber-500 text-black font-bold py-4 px-10 rounded-full hover:bg-amber-400 transition shadow-[0_0_30px_rgba(245,158,11,0.4)] flex items-center">
                Заказать вывоз
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 ml-2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
            </button>
        </div>
    </div>

    {{-- 2. КАЛЬКУЛЯТОР (Dark Theme) --}}
    <div id="calculator-section" class="container mx-auto px-4 -mt-20 relative z-20 mb-24">
        <div class="bg-slate-900 rounded-3xl shadow-2xl border border-slate-800 overflow-hidden flex flex-col lg:flex-row min-h-[600px]">
            
            {{-- Левая часть: Выбор предметов --}}
            <div class="w-full lg:w-2/3 p-8 lg:p-12 bg-slate-900">
                <h2 class="text-3xl font-bold text-white mb-2 flex items-center">
                    <span class="w-2 h-8 bg-amber-500 rounded-full mr-3"></span>
                    Что нужно вывезти?
                </h2>
                <p class="text-slate-400 mb-8">Выберите категорию и добавьте предметы в корзину</p>
                
                {{-- Категории --}}
                <div class="flex space-x-3 overflow-x-auto pb-4 mb-6 no-scrollbar">
                    <button @click="category = 'furniture'" 
                            :class="category === 'furniture' ? 'bg-amber-500 text-black border-amber-500' : 'bg-slate-800 text-slate-300 border-slate-700'"
                            class="px-6 py-3 rounded-xl font-bold border-2 transition whitespace-nowrap">
                        🛋 Мебель
                    </button>
                    <button @click="category = 'appliances'" 
                            :class="category === 'appliances' ? 'bg-amber-500 text-black border-amber-500' : 'bg-slate-800 text-slate-300 border-slate-700'"
                            class="px-6 py-3 rounded-xl font-bold border-2 transition whitespace-nowrap">
                        🔌 Техника
                    </button>
                    <button @click="category = 'junk'" 
                            :class="category === 'junk' ? 'bg-amber-500 text-black border-amber-500' : 'bg-slate-800 text-slate-300 border-slate-700'"
                            class="px-6 py-3 rounded-xl font-bold border-2 transition whitespace-nowrap">
                        📦 Хлам/Мешки
                    </button>
                </div>

                {{-- Сетка предметов --}}
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 max-h-96 overflow-y-auto pr-2 custom-scrollbar">
                    <template x-for="item in getItemsByCategory()" :key="item.id">
                        <div @click="addItem(item)" 
                             class="bg-slate-800 border-2 border-slate-700 rounded-xl p-4 cursor-pointer hover:border-amber-500 hover:bg-slate-800/50 transition flex flex-col items-center text-center group relative">
                            <div class="text-4xl mb-3 group-hover:scale-125 transition-transform duration-300" x-text="item.icon"></div>
                            <div class="font-semibold text-sm text-white mb-1" x-text="item.name"></div>
                            <div class="text-xs text-amber-500 font-bold" x-text="item.price + ' kr'"></div>
                            <div class="absolute top-2 right-2 w-6 h-6 bg-amber-500/20 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                                <span class="text-amber-500 text-xs font-bold">+</span>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Корзина --}}
                <div x-show="cart.length > 0" class="mt-8 border-t border-slate-700 pt-6" style="display: none;">
                    <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-4 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 mr-2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
                        </svg>
                        Ваш список на утилизацию (<span x-text="cart.length"></span>)
                    </h3>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="(item, index) in cart" :key="index">
                            <div class="bg-slate-800 border border-slate-700 rounded-lg pl-3 pr-2 py-2 flex items-center text-sm group hover:border-amber-500 transition">
                                <span class="text-white" x-text="item.name"></span>
                                <span class="ml-2 text-amber-500 font-bold" x-text="item.price + ' kr'"></span>
                                <button @click="removeItem(index)" class="ml-3 text-slate-400 hover:text-red-400 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" /></svg>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Правая часть: Смета --}}
            <div class="w-full lg:w-1/3 bg-slate-950 border-l border-slate-800 p-8 lg:p-12 flex flex-col justify-between relative">
                <div class="absolute top-0 right-0 w-64 h-64 opacity-5 pointer-events-none">
                    <svg fill="currentColor" viewBox="0 0 24 24" class="w-full h-full text-amber-500">
                        <path d="M12 22c5.5 0 10-4.5 10-10S17.5 2 12 2 2 6.5 2 12s4.5 10 10 10zm0-2c-4.4 0-8-3.6-8-8s3.6-8 8-8 8 3.6 8 8-3.6 8-8 8zm-1-12v6h4v-2h-2v-4h-2z"/>
                    </svg>
                </div>
                
                <div class="relative z-10">
                    <h3 class="text-2xl font-bold mb-6 text-white flex items-center">
                        <span class="w-2 h-6 bg-amber-500 rounded-full mr-3"></span>
                        Смета утилизации
                    </h3>
                    
                    {{-- Green Meter --}}
                    <div class="mb-8 bg-slate-900 rounded-xl p-6 border border-emerald-500/30 shadow-[0_0_20px_rgba(16,185,129,0.1)]">
                        <div class="flex justify-between text-xs text-emerald-300 mb-3">
                            <span class="font-bold">Влияние на природу</span>
                            <span class="font-bold" x-text="(estimateData.recycling_percentage || 95) + '%'"></span>
                        </div>
                        <div class="w-full bg-slate-800 rounded-full h-3 mb-2 overflow-hidden">
                            <div class="bg-gradient-to-r from-emerald-400 to-emerald-600 h-3 rounded-full transition-all duration-500" 
                                 :style="'width: ' + (estimateData.recycling_percentage || 95) + '%'"></div>
                        </div>
                        <div class="flex justify-between text-xs mt-3">
                            <span class="text-emerald-400">♻️ Переработка</span>
                            <span class="text-amber-400" x-text="(estimateData.co2_saved_kg || 0) + ' кг CO₂ сэкономлено'"></span>
                        </div>
                    </div>

                    {{-- Цены --}}
                    <div class="space-y-4 text-slate-300 mb-6">
                        <div class="flex justify-between items-center">
                            <span>Базовый выезд</span>
                            <span class="font-bold text-white">499 kr</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span>Предметы (<span x-text="cart.length"></span> шт.)</span>
                            <span class="font-bold text-amber-400" x-text="itemsTotal + ' kr'"></span>
                        </div>
                        <div class="pt-4 border-t border-slate-700">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-bold text-white">Итого:</span>
                                <span class="text-3xl font-black text-amber-500" x-text="(499 + itemsTotal) + ' kr'"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <button @click="openCheckout()" 
                        :disabled="cart.length === 0 || isSubmitting"
                        class="w-full bg-amber-500 text-black font-bold py-4 px-6 rounded-xl hover:bg-amber-400 transition shadow-[0_0_20px_rgba(245,158,11,0.3)] disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center">
                    <span x-show="!isSubmitting">Перейти к оформлению</span>
                    <span x-show="isSubmitting" class="flex items-center">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-black" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Оформление...
                    </span>
                </button>
            </div>
        </div>
    </div>

    {{-- 3. SMART CROSS-SELL (Умная подсказка) --}}
    <div x-show="showSmartOffer" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="fixed inset-0 z-50 flex items-center justify-center px-4 backdrop-blur-sm bg-black/70" 
         style="display: none;"
         @click.away="showSmartOffer = false">
        <div @click.stop class="bg-slate-900 rounded-3xl shadow-2xl max-w-3xl w-full overflow-hidden border border-slate-700">
            <div class="bg-gradient-to-r from-amber-500 to-amber-600 p-6 text-black flex justify-between items-center">
                <h3 class="text-2xl font-bold flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 mr-2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" /></svg>
                    Умное предложение
                </h3>
                <button @click="showSmartOffer = false" class="text-black/70 hover:text-black transition">✕</button>
            </div>
            
            <div class="p-8">
                <p class="text-slate-300 text-lg mb-6">
                    Вы утилизируете <strong class="text-white">старую мебель</strong>. Обычно в таких случаях нашим клиентам нужно привезти что-то взамен.
                </p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <a href="/category/delivery" class="border-2 border-dashed border-slate-700 rounded-xl p-6 hover:border-amber-500 hover:bg-slate-800 transition cursor-pointer group">
                        <div class="text-amber-500 font-bold mb-2 group-hover:underline text-lg">Купили новый диван?</div>
                        <p class="text-sm text-slate-400 mb-4">Закажите доставку из IKEA/Bohus, и мы привезем новый, когда будем забирать старый.</p>
                        <div class="text-amber-400 text-sm font-medium flex items-center">
                            Добавить доставку
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 ml-1"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                        </div>
                    </a>

                    <a href="/category/moving" class="border-2 border-dashed border-slate-700 rounded-xl p-6 hover:border-amber-500 hover:bg-slate-800 transition cursor-pointer group">
                        <div class="text-amber-500 font-bold mb-2 group-hover:underline text-lg">Это переезд?</div>
                        <p class="text-sm text-slate-400 mb-4">Если вы очищаете квартиру, мы можем организовать полный переезд и уборку.</p>
                        <div class="text-amber-400 text-sm font-medium flex items-center">
                            Рассчитать переезд
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 ml-1"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                        </div>
                    </a>
                </div>
            </div>

            <div class="bg-slate-800 border-t border-slate-700 p-6 flex justify-end space-x-3">
                <button @click="showSmartOffer = false; submitOrder()" class="text-slate-400 hover:text-white px-6 py-2 transition">Нет, только вывоз</button>
                <button @click="showSmartOffer = false; submitOrder()" class="bg-amber-500 text-black px-8 py-3 rounded-xl font-bold hover:bg-amber-400 transition">Оформить заказ</button>
            </div>
        </div>
    </div>

    {{-- 4. МОДАЛЬНОЕ ОКНО ОФОРМЛЕНИЯ --}}
    <div x-show="showCheckout" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="fixed inset-0 z-50 flex items-center justify-center px-4 backdrop-blur-sm bg-black/70" 
         style="display: none;"
         @click.away="showCheckout = false">
        <div @click.stop class="bg-slate-900 rounded-3xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto border border-slate-700">
            <div class="sticky top-0 bg-slate-900 border-b border-slate-700 p-6 flex items-center justify-between z-10">
                <h2 class="text-2xl font-bold text-white">Оформление заказа</h2>
                <button @click="showCheckout = false" class="text-slate-400 hover:text-white transition">✕</button>
            </div>

            <div class="p-6 space-y-6">
                {{-- Контактные данные --}}
                <div>
                    <h3 class="text-lg font-bold text-white mb-4">Контактные данные</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Имя</label>
                            <input type="text" x-model="checkoutForm.customer.name" 
                                   class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 focus:ring-amber-500 focus:border-amber-500 text-white" 
                                   placeholder="Иван Иванов" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Телефон</label>
                            <input type="tel" x-model="checkoutForm.customer.phone" 
                                   class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 focus:ring-amber-500 focus:border-amber-500 text-white" 
                                   placeholder="+47 123 45 678" required>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Email</label>
                            <input type="email" x-model="checkoutForm.customer.email" 
                                   class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 focus:ring-amber-500 focus:border-amber-500 text-white" 
                                   placeholder="ivan@example.com" required>
                        </div>
                    </div>
                </div>

                {{-- Адрес --}}
                <div>
                    <h3 class="text-lg font-bold text-white mb-4">Адрес вывоза</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Улица и дом</label>
                            <input type="text" x-model="checkoutForm.address.street" 
                                   class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 focus:ring-amber-500 focus:border-amber-500 text-white" 
                                   placeholder="Kongens gate 1" required>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Почтовый индекс</label>
                                <input type="text" x-model="checkoutForm.address.postal_code" 
                                       class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 focus:ring-amber-500 focus:border-amber-500 text-white" 
                                       placeholder="8500" required>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Город</label>
                                <input type="text" x-model="checkoutForm.address.city" 
                                       class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 focus:ring-amber-500 focus:border-amber-500 text-white" 
                                       value="Narvik" required>
                            </div>
                        </div>
                        <button @click="getCurrentLocation()" class="text-sm text-amber-400 hover:text-amber-300 flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 mr-1">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                            </svg>
                            Использовать текущее местоположение
                        </button>
                    </div>
                </div>

                {{-- Дата и время --}}
                <div>
                    <h3 class="text-lg font-bold text-white mb-4">Дата и время</h3>
                    <input type="date" x-model="checkoutForm.scheduled_at" 
                           :min="minDate"
                           class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 focus:ring-amber-500 focus:border-amber-500 text-white" 
                           required>
                </div>

                {{-- Комментарий --}}
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Комментарий (необязательно)</label>
                    <textarea x-model="checkoutForm.notes" 
                              class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 focus:ring-amber-500 focus:border-amber-500 text-white h-24" 
                              placeholder="Особые инструкции, доступ к подъезду и т.д."></textarea>
                </div>

                {{-- Итого --}}
                <div class="bg-slate-800 rounded-xl p-6 border border-slate-700">
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-slate-400">Итого к оплате:</span>
                        <span class="text-3xl font-black text-amber-500" x-text="(499 + itemsTotal) + ' kr'"></span>
                    </div>
                    <button @click="submitOrder()" 
                            :disabled="isSubmitting"
                            class="w-full bg-amber-500 text-black font-bold py-4 px-6 rounded-xl hover:bg-amber-400 transition shadow-lg disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!isSubmitting">Оформить заказ</span>
                        <span x-show="isSubmitting" class="flex items-center justify-center">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-black" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Оформление...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- 5. ИНФОБЛОК О ПЕРЕРАБОТКЕ --}}
    <div class="container mx-auto px-4 pb-24">
        <h2 class="text-3xl font-bold text-center mb-16 text-white">Куда уедет ваш мусор?</h2>
        <div class="flex flex-col md:flex-row justify-center items-center gap-8">
            <div class="w-full md:w-1/4 p-8 bg-slate-900 rounded-2xl border border-slate-800 hover:border-amber-500/50 transition group">
                <div class="text-5xl mb-4 group-hover:scale-110 transition">♻️</div>
                <h4 class="font-bold text-xl text-white mb-3">Вторичка</h4>
                <p class="text-sm text-slate-400">Металл, картон и пластик отправляются на заводы переработки.</p>
            </div>
            <div class="hidden md:block text-amber-500 text-3xl">➜</div>
            <div class="w-full md:w-1/4 p-8 bg-slate-900 rounded-2xl border border-slate-800 hover:border-amber-500/50 transition group">
                <div class="text-5xl mb-4 group-hover:scale-110 transition">🪵</div>
                <h4 class="font-bold text-xl text-white mb-3">Энергия</h4>
                <p class="text-sm text-slate-400">Дерево и биоматериалы превращаются в тепло для домов Нарвика.</p>
            </div>
            <div class="hidden md:block text-amber-500 text-3xl">➜</div>
            <div class="w-full md:w-1/4 p-8 bg-slate-900 rounded-2xl border border-slate-800 hover:border-amber-500/50 transition group">
                <div class="text-5xl mb-4 group-hover:scale-110 transition">🎁</div>
                <h4 class="font-bold text-xl text-white mb-3">Благотворительность</h4>
                <p class="text-sm text-slate-400">Исправная мебель передается в Fretex или нуждающимся семьям.</p>
            </div>
        </div>
    </div>

</div>

<style>
@keyframes fade-in-down {
    0% {
        opacity: 0;
        transform: translateY(-20px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in-down {
    animation: fade-in-down 0.6s ease-out;
}

.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(15, 23, 42, 0.5);
    border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(245, 158, 11, 0.5);
    border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(245, 158, 11, 0.7);
}

.no-scrollbar::-webkit-scrollbar {
    display: none;
}
</style>

<script>
function ecoPage() {
    return {
        category: 'furniture',
        cart: [],
        showSmartOffer: false,
        showCheckout: false,
        isSubmitting: false,
        estimateData: {
            recycling_percentage: 95,
            co2_saved_kg: 0,
        },
        minDate: new Date().toISOString().split('T')[0],
        
        checkoutForm: {
            customer: {
                name: '',
                email: '',
                phone: '',
            },
            address: {
                street: '',
                city: 'Narvik',
                postal_code: '',
                lat: null,
                lng: null,
            },
            scheduled_at: '',
            notes: '',
        },
        
        items: {
            furniture: [
                {id: 1, name: 'Диван (2-мест)', price: 350, icon: '🛋', category: 'furniture'},
                {id: 2, name: 'Диван (Угловой)', price: 550, icon: '🛋', category: 'furniture'},
                {id: 3, name: 'Кровать (1-сп)', price: 300, icon: '🛏', category: 'furniture'},
                {id: 4, name: 'Матрас', price: 200, icon: '🛏', category: 'furniture'},
                {id: 5, name: 'Шкаф (разобр.)', price: 250, icon: '🚪', category: 'furniture'},
                {id: 6, name: 'Стол/Стулья', price: 200, icon: '🪑', category: 'furniture'},
                {id: 7, name: 'Комод', price: 180, icon: '🗄️', category: 'furniture'},
                {id: 8, name: 'Кресло', price: 150, icon: '🪑', category: 'furniture'},
            ],
            appliances: [
                {id: 10, name: 'Холодильник', price: 400, icon: '❄️', category: 'appliances'},
                {id: 11, name: 'Стир. машина', price: 400, icon: '🧺', category: 'appliances'},
                {id: 12, name: 'Плита', price: 350, icon: '🍳', category: 'appliances'},
                {id: 13, name: 'Телевизор', price: 150, icon: '📺', category: 'appliances'},
                {id: 14, name: 'Микроволновка', price: 100, icon: '📻', category: 'appliances'},
                {id: 15, name: 'Посудомойка', price: 350, icon: '💧', category: 'appliances'},
            ],
            junk: [
                {id: 20, name: 'Мешок (100л)', price: 100, icon: '⚫️', category: 'junk'},
                {id: 21, name: 'Коробка', price: 50, icon: '📦', category: 'junk'},
                {id: 22, name: 'Шины (4шт)', price: 300, icon: '🚗', category: 'junk'},
                {id: 23, name: 'Строймусор', price: 200, icon: '🧱', category: 'junk'},
                {id: 24, name: 'Старые окна', price: 150, icon: '🪟', category: 'junk'},
            ]
        },

        initPage() {
            // Set default date to tomorrow
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            this.checkoutForm.scheduled_at = tomorrow.toISOString().split('T')[0];
            
            // Calculate initial estimate
            this.updateEstimate();
        },

        getItemsByCategory() {
            return this.items[this.category] || [];
        },

        addItem(item) {
            this.cart.push({...item});
            this.updateEstimate();
        },

        removeItem(index) {
            this.cart.splice(index, 1);
            this.updateEstimate();
        },

        get itemsTotal() {
            return this.cart.reduce((sum, item) => sum + item.price, 0);
        },

        updateEstimate() {
            if (this.cart.length === 0) {
                this.estimateData.recycling_percentage = 95;
                this.estimateData.co2_saved_kg = 0;
                return;
            }

            // Calculate recycling percentage and CO2 saved
            const response = fetch('/api/v1/eco/calculate-price', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({
                    items: this.cart.map(item => ({
                        id: item.id,
                        name: item.name,
                        price: item.price,
                        quantity: 1,
                    })),
                }),
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.estimateData.recycling_percentage = data.data.recycling_percentage;
                    this.estimateData.co2_saved_kg = data.data.co2_saved_kg;
                }
            })
            .catch(err => {
                console.error('Estimate calculation error:', err);
                // Fallback values
                this.estimateData.recycling_percentage = Math.min(95, 50 + (this.cart.length * 5));
                this.estimateData.co2_saved_kg = this.cart.length * 12.5;
            });
        },

        scrollToCalculator() {
            document.getElementById('calculator-section').scrollIntoView({ behavior: 'smooth' });
        },

        openCheckout() {
            if (this.cart.length === 0) {
                alert('Добавьте хотя бы один предмет');
                return;
            }
            
            // ЛОГИКА SMART UPSELL
            const hasFurniture = this.cart.some(i => i.category === 'furniture');
            const isBigOrder = this.cart.length > 4;

            if (hasFurniture || isBigOrder) {
                this.showSmartOffer = true;
            } else {
                this.showCheckout = true;
            }
        },

        getCurrentLocation() {
            if (!navigator.geolocation) {
                alert('Геолокация не поддерживается вашим браузером');
                return;
            }
            
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.checkoutForm.address.lat = position.coords.latitude;
                    this.checkoutForm.address.lng = position.coords.longitude;
                    
                    // Reverse geocoding
                    this.reverseGeocode(position.coords.latitude, position.coords.longitude);
                },
                (error) => {
                    console.error('Geolocation error:', error);
                    alert('Не удалось получить ваше местоположение');
                }
            );
        },

        async reverseGeocode(lat, lng) {
            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`);
                const data = await response.json();
                
                if (data.address) {
                    const addr = data.address;
                    this.checkoutForm.address.street = `${addr.road || ''} ${addr.house_number || ''}`.trim();
                    this.checkoutForm.address.postal_code = addr.postcode || '';
                    this.checkoutForm.address.city = addr.city || addr.town || addr.village || 'Narvik';
                }
            } catch (error) {
                console.error('Reverse geocoding error:', error);
            }
        },

        async submitOrder() {
            // Validation
            if (!this.checkoutForm.customer.name || !this.checkoutForm.customer.email || !this.checkoutForm.customer.phone) {
                alert('Пожалуйста, заполните все контактные данные');
                return;
            }
            
            if (!this.checkoutForm.address.street || !this.checkoutForm.address.postal_code) {
                alert('Пожалуйста, укажите адрес вывоза');
                return;
            }
            
            if (!this.checkoutForm.scheduled_at) {
                alert('Пожалуйста, выберите дату вывоза');
                return;
            }

            this.isSubmitting = true;

            try {
                const payload = {
                    customer: this.checkoutForm.customer,
                    address: {
                        ...this.checkoutForm.address,
                        lat: this.checkoutForm.address.lat || 68.4372,
                        lng: this.checkoutForm.address.lng || 17.4256,
                    },
                    items: this.cart.map(item => ({
                        id: item.id,
                        name: item.name,
                        price: item.price,
                        quantity: 1,
                        category: item.category,
                    })),
                    scheduled_at: this.checkoutForm.scheduled_at,
                    notes: this.checkoutForm.notes,
                };

                const response = await fetch('/api/v1/eco/orders', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify(payload),
                });

                const data = await response.json();

                if (data.success) {
                    alert(`Заказ успешно создан! Номер заказа: ${data.data.order_number}`);
                    // Redirect to order page or home
                    window.location.href = '/';
                } else {
                    alert('Ошибка при создании заказа: ' + (data.message || 'Неизвестная ошибка'));
                    console.error('Order creation error:', data);
                }
            } catch (error) {
                console.error('Network error:', error);
                alert('Ошибка сети. Пожалуйста, попробуйте еще раз.');
            } finally {
                this.isSubmitting = false;
            }
        }
    }
}
</script>
@endsection
