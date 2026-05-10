@extends('layouts.app')

@section('content')
{{-- Toast Notifications Container --}}
<div x-data="{ toasts: [] }" 
     x-init="window.showToast = (message, type = 'info') => {
         const id = Date.now();
         toasts.push({ id, message, type });
         setTimeout(() => {
             const index = toasts.findIndex(t => t.id === id);
             if (index > -1) toasts.splice(index, 1);
         }, 5000);
     }"
     class="fixed top-4 right-4 z-[9999] space-y-2">
    <template x-for="toast in toasts" :key="toast.id">
        <div x-show="true"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-x-full"
             x-transition:enter-end="opacity-100 translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-x-0"
             x-transition:leave-end="opacity-0 translate-x-full"
             :class="{
                 'bg-green-500': toast.type === 'success',
                 'bg-red-500': toast.type === 'error',
                 'bg-blue-500': toast.type === 'info',
                 'bg-amber-500': toast.type === 'warning'
             }"
             class="text-white px-6 py-4 rounded-lg shadow-2xl flex items-center space-x-3 min-w-[300px] max-w-md">
            <span x-text="toast.type === 'success' ? '✓' : toast.type === 'error' ? '✕' : toast.type === 'warning' ? '⚠' : 'ℹ'"
                  class="text-2xl font-bold"></span>
            <span x-text="toast.message" class="flex-1"></span>
            <button @click="toasts = toasts.filter(t => t.id !== toast.id)" class="text-white/80 hover:text-white">×</button>
        </div>
    </template>
</div>

<div x-data="movingPage()" x-init="initPage()" class="min-h-screen bg-slate-50 font-sans selection:bg-amber-500 selection:text-black">

    {{-- 1. HERO SECTION: Миграция Улья --}}
    <div class="relative h-[650px] md:h-[750px] overflow-hidden bg-slate-900">
        <div class="absolute inset-0">
            <img src="https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=2070&q=80" 
                 class="w-full h-full object-cover opacity-40 animate-pulse-slow">
            <div class="absolute inset-0 bg-gradient-to-r from-slate-950 via-slate-900/80 to-transparent"></div>
        </div>
        
        {{-- Animated Honeycomb Pattern --}}
        <div class="absolute top-0 right-0 w-1/2 h-full opacity-10 pointer-events-none animate-float" 
             style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cpath d=\'M30 0l25.98 15v30L30 60 4.02 45V15z\' fill-rule=\'evenodd\' stroke=\'%23fbbf24\' fill=\'none\'/%3E%3C/svg%3E');">
        </div>

        {{-- Floating Particles --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute top-1/4 left-1/4 w-2 h-2 bg-amber-500 rounded-full opacity-60 animate-float" style="animation-delay: 0s; animation-duration: 6s;"></div>
            <div class="absolute top-1/3 right-1/4 w-3 h-3 bg-orange-500 rounded-full opacity-40 animate-float" style="animation-delay: 2s; animation-duration: 8s;"></div>
            <div class="absolute bottom-1/4 left-1/3 w-2 h-2 bg-amber-400 rounded-full opacity-50 animate-float" style="animation-delay: 4s; animation-duration: 7s;"></div>
        </div>

        <div class="relative container mx-auto px-4 h-full flex items-center z-10">
            <div class="max-w-3xl text-white">
                <div class="inline-flex items-center space-x-2 mb-6 animate-fade-in">
                    <span class="bg-amber-500 text-black text-xs font-bold px-3 py-1 rounded uppercase tracking-widest shadow-lg transform hover:scale-105 transition">BiKube Moving</span>
                    <span class="border border-amber-500/50 text-amber-500 text-xs px-3 py-1 rounded uppercase tracking-widest backdrop-blur-sm hover:bg-amber-500/10 transition">Safe Migration</span>
                </div>
                
                <h1 class="text-5xl md:text-7xl font-black mb-6 leading-tight animate-fade-in-up">
                    Переезд<br>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-400 via-orange-400 to-orange-500 animate-gradient">организованный как улей.</span>
                </h1>
                
                <p class="text-xl text-slate-300 mb-10 max-w-2xl border-l-4 border-amber-500 pl-6 animate-fade-in-up" style="animation-delay: 0.2s;">
                    Мы не просто перевозим коробки. Мы переносим вашу экосистему.
                    Слаженная работа команды, сотовая защита вещей и полная страховка.
                </p>
                
                <div class="flex flex-col sm:flex-row gap-4 animate-fade-in-up" style="animation-delay: 0.4s;">
                    <button @click="scrollToCalculator()" 
                            class="group bg-amber-500 text-black font-bold py-4 px-10 rounded-full hover:bg-amber-400 transition-all shadow-[0_0_20px_rgba(245,158,11,0.4)] transform hover:-translate-y-1 hover:scale-105 hover:shadow-[0_0_30px_rgba(245,158,11,0.6)]">
                        <span class="flex items-center justify-center">
                            Рассчитать миграцию
                            <svg class="w-5 h-5 ml-2 transform group-hover:translate-x-1 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                        </span>
                    </button>
                    <button @click="openPhotoEstimate()" 
                            class="group border-2 border-slate-500 hover:border-amber-400 text-slate-300 hover:text-white font-bold py-4 px-8 rounded-full transition-all flex items-center justify-center backdrop-blur-sm bg-slate-900/30 hover:bg-slate-900/50 transform hover:scale-105">
                        <span class="mr-2 text-2xl group-hover:scale-125 transition-transform">📸</span>
                        Оценка по фото
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. ТИПЫ МИГРАЦИИ (Cards) --}}
    <div class="container mx-auto px-4 -mt-24 relative z-20 mb-20">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-slate-800 rounded-2xl p-8 shadow-2xl border-t-4 border-amber-500 hover:bg-slate-750 transition-all group transform hover:-translate-y-2 hover:shadow-amber-500/20">
                <div class="w-16 h-16 bg-slate-900 rounded-2xl flex items-center justify-center mb-6 border border-slate-700 group-hover:border-amber-500/50 group-hover:bg-amber-500/10 transition-all transform group-hover:scale-110">
                    <span class="text-3xl transform group-hover:scale-125 transition">🏠</span>
                </div>
                <h3 class="text-2xl font-bold text-white mb-3 group-hover:text-amber-400 transition">Жилая миграция</h3>
                <p class="text-slate-400 mb-6">Переезд квартиры или дома. Грузчики работают слаженно, как единый организм.</p>
                <ul class="space-y-2 text-sm text-slate-300">
                    <li class="flex items-center transform group-hover:translate-x-1 transition"><span class="text-amber-500 mr-2">✓</span> Упаковка в сотовый картон</li>
                    <li class="flex items-center transform group-hover:translate-x-1 transition" style="transition-delay: 0.1s;"><span class="text-amber-500 mr-2">✓</span> Сборка мебели</li>
                </ul>
            </div>

            <div class="bg-slate-800 rounded-2xl p-8 shadow-2xl border-t-4 border-amber-500 hover:bg-slate-750 transition-all group transform hover:-translate-y-2 hover:shadow-amber-500/20">
                <div class="w-16 h-16 bg-slate-900 rounded-2xl flex items-center justify-center mb-6 border border-slate-700 group-hover:border-amber-500/50 group-hover:bg-amber-500/10 transition-all transform group-hover:scale-110">
                    <span class="text-3xl transform group-hover:scale-125 transition">🏢</span>
                </div>
                <h3 class="text-2xl font-bold text-white mb-3 group-hover:text-amber-400 transition">Бизнес-рой</h3>
                <p class="text-slate-400 mb-6">Офисный переезд без остановки работы. Мы перевезем ваш бизнес за одну ночь.</p>
                <ul class="space-y-2 text-sm text-slate-300">
                    <li class="flex items-center transform group-hover:translate-x-1 transition"><span class="text-amber-500 mr-2">✓</span> Ночной режим работы</li>
                    <li class="flex items-center transform group-hover:translate-x-1 transition" style="transition-delay: 0.1s;"><span class="text-amber-500 mr-2">✓</span> Маркировка рабочих мест</li>
                </ul>
            </div>

            <div class="bg-slate-800 rounded-2xl p-8 shadow-2xl border-t-4 border-amber-500 hover:bg-slate-750 transition-all group transform hover:-translate-y-2 hover:shadow-amber-500/20">
                <div class="w-16 h-16 bg-slate-900 rounded-2xl flex items-center justify-center mb-6 border border-slate-700 group-hover:border-amber-500/50 group-hover:bg-amber-500/10 transition-all transform group-hover:scale-110">
                    <span class="text-3xl transform group-hover:scale-125 transition">🚕</span>
                </div>
                <h3 class="text-2xl font-bold text-white mb-3 group-hover:text-amber-400 transition">Грузовой дрон</h3>
                <p class="text-slate-400 mb-6">Экспресс-доставка одного крупного предмета. Диван с Finn.no или холодильник.</p>
                <ul class="space-y-2 text-sm text-slate-300">
                    <li class="flex items-center transform group-hover:translate-x-1 transition"><span class="text-amber-500 mr-2">✓</span> Подача за 30 минут</li>
                    <li class="flex items-center transform group-hover:translate-x-1 transition" style="transition-delay: 0.1s;"><span class="text-amber-500 mr-2">✓</span> Фиксированная цена</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- 3. НОВАЯ ФИЧА: HONEYCOMB PROTECTION --}}
    <div class="bg-white py-20 overflow-hidden">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row items-center gap-12">
                <div class="w-full md:w-1/2 relative">
                    <div class="absolute inset-0 bg-amber-100/50 rounded-full blur-3xl transform -translate-x-10"></div>
                    <img src="https://images.unsplash.com/photo-1558618666-fcd25c85cd64?auto=format&fit=crop&w=1000&q=80" 
                         alt="Honeycomb Protection упаковка" 
                         class="relative rounded-2xl shadow-2xl border-8 border-white z-10 rotate-2 hover:rotate-0 transition duration-500 w-full h-auto object-cover"
                         loading="lazy"
                         onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'1000\' height=\'600\'%3E%3Crect fill=\'%23fbbf24\' width=\'1000\' height=\'600\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' font-size=\'32\' fill=\'%231f2937\' text-anchor=\'middle\' dominant-baseline=\'middle\' font-family=\'Arial\'%3EHoneycomb Protection%3C/text%3E%3C/svg%3E';">
                    
                    <div class="absolute -bottom-6 -right-6 bg-slate-900 text-white p-4 rounded-xl shadow-xl z-20 flex items-center border border-amber-500">
                        <div class="text-3xl mr-3">🛡️</div>
                        <div>
                            <div class="text-xs text-slate-400 uppercase">Protection Level</div>
                            <div class="font-bold text-amber-500">Honeycomb Grade A+</div>
                        </div>
                    </div>
                </div>
                
                <div class="w-full md:w-1/2">
                    <h2 class="text-4xl font-black text-slate-900 mb-6">Технология <span class="text-amber-600">Honeycomb Protection</span></h2>
                    <p class="text-lg text-slate-600 mb-6">
                        Пчелиные соты — самая прочная структура в природе. Мы используем этот принцип при упаковке.
                        Ваши хрупкие вещи оборачиваются в многослойный сотовый картон и пузырчатую пленку.
                    </p>
                    
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <div class="bg-amber-100 p-2 rounded-lg mr-4 mt-1">📦</div>
                            <div>
                                <h4 class="font-bold text-slate-900">Усиленные углы</h4>
                                <p class="text-sm text-slate-500">Защита от ударов при транспортировке.</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <div class="bg-amber-100 p-2 rounded-lg mr-4 mt-1">👔</div>
                            <div>
                                <h4 class="font-bold text-slate-900">Гардеробные короба</h4>
                                <p class="text-sm text-slate-500">Перевозка одежды на вешалках, без сминания.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 4. МНОГОШАГОВАЯ ФОРМА ЗАКАЗА --}}
    <div id="calculator-section" class="py-20 bg-slate-900 text-white">
        <div class="container mx-auto px-4">
            <div class="max-w-6xl mx-auto">
                <div class="bg-slate-800 rounded-3xl overflow-hidden shadow-2xl border border-slate-700">
                    
                    {{-- Progress Steps --}}
                    <div class="bg-slate-950 px-8 py-6 border-b border-slate-700">
                        <div class="flex items-center justify-between max-w-3xl mx-auto">
                            <template x-for="(step, index) in steps" :key="index">
                                <div class="flex items-center flex-1">
                                    <div class="flex flex-col items-center">
                                        <div class="w-10 h-10 rounded-full flex items-center justify-center border-2 transition-all"
                                             :class="currentStep > index ? 'bg-amber-500 border-amber-500 text-black' : (currentStep === index ? 'bg-slate-800 border-amber-500 text-amber-500' : 'bg-slate-800 border-slate-600 text-slate-500')">
                                            <span x-show="currentStep <= index" x-text="index + 1"></span>
                                            <span x-show="currentStep > index">✓</span>
                                        </div>
                                        <span class="text-xs mt-2 text-slate-400" x-text="step.label"></span>
                                    </div>
                                    <div x-show="index < steps.length - 1" class="flex-1 h-0.5 mx-4 transition-all"
                                         :class="currentStep > index ? 'bg-amber-500' : 'bg-slate-700'"></div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Form Content --}}
                    <div class="p-8 md:p-12">
                        {{-- STEP 1: Тип переезда --}}
                        <div x-show="currentStep === 0" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-4">
                            <h2 class="text-3xl font-bold mb-2">Выберите размер переезда</h2>
                            <p class="text-slate-400 mb-8">Это поможет нам рассчитать стоимость и время</p>
                            
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                                <template x-for="option in roomOptions" :key="option.id">
                                    <button @click="form.rooms = option.id; nextStep()" 
                                            :class="{'bg-amber-500 text-black border-amber-500': form.rooms === option.id, 'bg-slate-900 text-slate-400 border-slate-700 hover:border-slate-500': form.rooms !== option.id}"
                                            class="border rounded-xl p-6 flex flex-col items-center transition-all duration-300 hover:scale-105">
                                        <span class="text-4xl mb-3" x-text="option.icon"></span>
                                        <span class="text-sm font-bold" x-text="option.label"></span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        {{-- STEP 2: Адреса --}}
                        <div x-show="currentStep === 1" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-4" style="display: none;">
                            <h2 class="text-3xl font-bold mb-2">Откуда и куда переезжаем?</h2>
                            <p class="text-slate-400 mb-8">Укажите адреса отправления и назначения</p>
                            
                            <div class="grid md:grid-cols-2 gap-8">
                                {{-- From Address --}}
                                <div>
                                    <h3 class="text-xl font-bold mb-4 text-amber-400">Откуда</h3>
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-bold text-slate-400 uppercase mb-2">Улица и дом</label>
                                            <input type="text" x-model="form.from_address.street" placeholder="Kongens gate 1" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 focus:ring-amber-500 focus:border-amber-500">
                                        </div>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-bold text-slate-400 uppercase mb-2">Почтовый индекс</label>
                                                <input type="text" x-model="form.from_address.postal_code" placeholder="8500" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 focus:ring-amber-500 focus:border-amber-500">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-bold text-slate-400 uppercase mb-2">Город</label>
                                                <input type="text" x-model="form.from_address.city" placeholder="Narvik" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 focus:ring-amber-500 focus:border-amber-500">
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-bold text-slate-400 uppercase mb-2">Этаж</label>
                                                <input type="number" x-model="form.from_address.floor" min="0" placeholder="0" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 focus:ring-amber-500 focus:border-amber-500">
                                            </div>
                                            <div class="flex items-end">
                                                <label class="flex items-center p-4 bg-slate-900 rounded-xl border border-slate-700 cursor-pointer">
                                                    <input type="checkbox" x-model="form.from_address.has_elevator" class="w-5 h-5 rounded bg-slate-800 border-slate-600 text-amber-500 focus:ring-amber-500">
                                                    <span class="ml-3 text-sm">Есть лифт</span>
                                                </label>
                                            </div>
                                        </div>
                                        <button @click="getCurrentLocation('from')" type="button" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 hover:border-amber-500 transition text-sm">
                                            📍 Использовать текущее местоположение
                                        </button>
                                    </div>
                                </div>

                                {{-- To Address --}}
                                <div>
                                    <h3 class="text-xl font-bold mb-4 text-amber-400">Куда</h3>
                                    <div class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-bold text-slate-400 uppercase mb-2">Улица и дом</label>
                                            <input type="text" x-model="form.to_address.street" placeholder="Kongens gate 1" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 focus:ring-amber-500 focus:border-amber-500">
                                        </div>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-bold text-slate-400 uppercase mb-2">Почтовый индекс</label>
                                                <input type="text" x-model="form.to_address.postal_code" placeholder="8500" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 focus:ring-amber-500 focus:border-amber-500">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-bold text-slate-400 uppercase mb-2">Город</label>
                                                <input type="text" x-model="form.to_address.city" placeholder="Narvik" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 focus:ring-amber-500 focus:border-amber-500">
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-bold text-slate-400 uppercase mb-2">Этаж</label>
                                                <input type="number" x-model="form.to_address.floor" min="0" placeholder="0" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 focus:ring-amber-500 focus:border-amber-500">
                                            </div>
                                            <div class="flex items-end">
                                                <label class="flex items-center p-4 bg-slate-900 rounded-xl border border-slate-700 cursor-pointer">
                                                    <input type="checkbox" x-model="form.to_address.has_elevator" class="w-5 h-5 rounded bg-slate-800 border-slate-600 text-amber-500 focus:ring-amber-500">
                                                    <span class="ml-3 text-sm">Есть лифт</span>
                                                </label>
                                            </div>
                                        </div>
                                        <button @click="getCurrentLocation('to')" type="button" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 hover:border-amber-500 transition text-sm">
                                            📍 Использовать текущее местоположение
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-between mt-8">
                                <button @click="currentStep--" class="text-slate-400 hover:text-white px-6">Назад</button>
                                <button @click="nextStep()" class="bg-amber-500 text-black font-bold py-3 px-8 rounded-xl hover:bg-amber-400">Далее</button>
                            </div>
                        </div>

                        {{-- STEP 3: Дополнительные услуги --}}
                        <div x-show="currentStep === 2" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-4" style="display: none;">
                            <h2 class="text-3xl font-bold mb-2">Дополнительные услуги</h2>
                            <p class="text-slate-400 mb-8">Выберите дополнительные опции для вашего переезда</p>
                            
                            <div class="space-y-4 mb-8">
                                <label class="flex items-center p-4 bg-slate-900 rounded-xl border border-slate-700 cursor-pointer hover:border-amber-500/50 transition">
                                    <input type="checkbox" x-model="form.services.packing" class="w-5 h-5 rounded bg-slate-800 border-slate-600 text-amber-500 focus:ring-amber-500">
                                    <div class="ml-3 flex-1">
                                        <span class="font-medium">Honeycomb Protection (Упаковка)</span>
                                        <p class="text-sm text-slate-400">Профессиональная упаковка всех вещей</p>
                                    </div>
                                </label>
                                <label class="flex items-center p-4 bg-slate-900 rounded-xl border border-slate-700 cursor-pointer hover:border-amber-500/50 transition">
                                    <input type="checkbox" x-model="form.services.assembly" class="w-5 h-5 rounded bg-slate-800 border-slate-600 text-amber-500 focus:ring-amber-500">
                                    <div class="ml-3 flex-1">
                                        <span class="font-medium">Сборка мебели</span>
                                        <p class="text-sm text-slate-400">Сборка мебели на новом месте</p>
                                    </div>
                                </label>
                                <label class="flex items-center p-4 bg-slate-900 rounded-xl border border-slate-700 cursor-pointer hover:border-amber-500/50 transition">
                                    <input type="checkbox" x-model="form.services.disassembly" class="w-5 h-5 rounded bg-slate-800 border-slate-600 text-amber-500 focus:ring-amber-500">
                                    <div class="ml-3 flex-1">
                                        <span class="font-medium">Разборка мебели</span>
                                        <p class="text-sm text-slate-400">Разборка мебели перед переездом</p>
                                    </div>
                                </label>
                                <label class="flex items-center p-4 bg-slate-900 rounded-xl border border-slate-700 cursor-pointer hover:border-amber-500/50 transition">
                                    <input type="checkbox" x-model="form.services.wrapping" class="w-5 h-5 rounded bg-slate-800 border-slate-600 text-amber-500 focus:ring-amber-500">
                                    <div class="ml-3 flex-1">
                                        <span class="font-medium">Упаковка хрупких вещей</span>
                                        <p class="text-sm text-slate-400">Дополнительная защита для хрупких предметов</p>
                                    </div>
                                </label>
                            </div>

                            <div class="mb-8">
                                <label class="block text-sm font-bold text-slate-400 uppercase mb-4">Тип упаковки</label>
                                <div class="grid grid-cols-3 gap-4">
                                    <button @click="form.package_type = 'economy'" 
                                            :class="{'bg-amber-500 text-black border-amber-500': form.package_type === 'economy', 'bg-slate-900 text-slate-400 border-slate-700': form.package_type !== 'economy'}"
                                            class="border rounded-xl p-4 text-center transition">
                                        <div class="font-bold mb-1">Эконом</div>
                                        <div class="text-xs">Базовый уровень</div>
                                    </button>
                                    <button @click="form.package_type = 'standard'" 
                                            :class="{'bg-amber-500 text-black border-amber-500': form.package_type === 'standard', 'bg-slate-900 text-slate-400 border-slate-700': form.package_type !== 'standard'}"
                                            class="border rounded-xl p-4 text-center transition">
                                        <div class="font-bold mb-1">Стандарт</div>
                                        <div class="text-xs">Рекомендуется</div>
                                    </button>
                                    <button @click="form.package_type = 'premium'" 
                                            :class="{'bg-amber-500 text-black border-amber-500': form.package_type === 'premium', 'bg-slate-900 text-slate-400 border-slate-700': form.package_type !== 'premium'}"
                                            class="border rounded-xl p-4 text-center transition">
                                        <div class="font-bold mb-1">Премиум</div>
                                        <div class="text-xs">Максимальная защита</div>
                                    </button>
                                </div>
                            </div>

                            <div class="flex justify-between mt-8">
                                <button @click="currentStep--" class="text-slate-400 hover:text-white px-6">Назад</button>
                                <button @click="nextStep()" class="bg-amber-500 text-black font-bold py-3 px-8 rounded-xl hover:bg-amber-400">Далее</button>
                            </div>
                        </div>

                        {{-- STEP 4: Контакты и дата --}}
                        <div x-show="currentStep === 3" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-4" style="display: none;">
                            <h2 class="text-3xl font-bold mb-2">Контактные данные и дата</h2>
                            <p class="text-slate-400 mb-8">Укажите ваши контакты и желаемую дату переезда</p>
                            
                            <div class="grid md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label class="block text-sm font-bold text-slate-400 uppercase mb-2">Имя</label>
                                    <input type="text" x-model="form.customer.name" required placeholder="Иван Иванов" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 focus:ring-amber-500 focus:border-amber-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-400 uppercase mb-2">Телефон</label>
                                    <input type="tel" x-model="form.customer.phone" required placeholder="+47 123 45 678" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 focus:ring-amber-500 focus:border-amber-500">
                                </div>
                            </div>
                            <div class="mb-6">
                                <label class="block text-sm font-bold text-slate-400 uppercase mb-2">Email</label>
                                <input type="email" x-model="form.customer.email" required placeholder="ivan@example.com" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 focus:ring-amber-500 focus:border-amber-500">
                            </div>
                            <div class="mb-6">
                                <label class="block text-sm font-bold text-slate-400 uppercase mb-2">Дата переезда</label>
                                <input type="date" x-model="form.scheduled_at" :min="minDate" required class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 focus:ring-amber-500 focus:border-amber-500">
                            </div>
                            <div class="mb-6">
                                <label class="block text-sm font-bold text-slate-400 uppercase mb-2">Комментарий (необязательно)</label>
                                <textarea x-model="form.notes" rows="3" placeholder="Дополнительная информация о переезде..." class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 focus:ring-amber-500 focus:border-amber-500"></textarea>
                            </div>

                            <div class="flex justify-between mt-8">
                                <button @click="currentStep--" class="text-slate-400 hover:text-white px-6">Назад</button>
                                <button @click="nextStep()" class="bg-amber-500 text-black font-bold py-3 px-8 rounded-xl hover:bg-amber-400">Далее</button>
                            </div>
                        </div>

                        {{-- STEP 5: Подтверждение и отправка --}}
                        <div x-show="currentStep === 4" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-4" style="display: none;">
                            <h2 class="text-3xl font-bold mb-2">Подтверждение заказа</h2>
                            <p class="text-slate-400 mb-8">Проверьте данные перед отправкой</p>
                            
                            <div class="grid md:grid-cols-2 gap-6 mb-6">
                                <div class="bg-slate-900 rounded-xl p-6 space-y-4">
                                    <div>
                                        <h4 class="text-amber-400 font-bold mb-2">Тип переезда:</h4>
                                        <p x-text="roomOptions.find(r => r.id === form.rooms)?.label"></p>
                                    </div>
                                    <div>
                                        <h4 class="text-amber-400 font-bold mb-2">Откуда:</h4>
                                        <p x-text="form.from_address.street + ', ' + form.from_address.postal_code + ' ' + form.from_address.city"></p>
                                        <p class="text-xs text-slate-500 mt-1" x-show="form.from_address.floor">
                                            Этаж: <span x-text="form.from_address.floor"></span>
                                            <span x-show="form.from_address.has_elevator"> • Есть лифт</span>
                                        </p>
                                    </div>
                                    <div>
                                        <h4 class="text-amber-400 font-bold mb-2">Куда:</h4>
                                        <p x-text="form.to_address.street + ', ' + form.to_address.postal_code + ' ' + form.to_address.city"></p>
                                        <p class="text-xs text-slate-500 mt-1" x-show="form.to_address.floor">
                                            Этаж: <span x-text="form.to_address.floor"></span>
                                            <span x-show="form.to_address.has_elevator"> • Есть лифт</span>
                                        </p>
                                    </div>
                                    <div>
                                        <h4 class="text-amber-400 font-bold mb-2">Контакт:</h4>
                                        <p x-text="form.customer.name + ' - ' + form.customer.phone"></p>
                                        <p class="text-xs text-slate-500" x-text="form.customer.email"></p>
                                    </div>
                                    <div>
                                        <h4 class="text-amber-400 font-bold mb-2">Дата:</h4>
                                        <p x-text="new Date(form.scheduled_at).toLocaleDateString('ru-RU', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })"></p>
                                    </div>
                                    <div x-show="form.notes">
                                        <h4 class="text-amber-400 font-bold mb-2">Комментарий:</h4>
                                        <p class="text-sm text-slate-300" x-text="form.notes"></p>
                                    </div>
                                </div>
                                
                                {{-- Price Estimate Card --}}
                                <div class="bg-gradient-to-br from-amber-500/20 to-orange-500/20 rounded-xl p-6 border border-amber-500/30">
                                    <h3 class="text-xl font-bold text-amber-400 mb-4 flex items-center">
                                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Предварительная стоимость
                                    </h3>
                                    <div x-show="estimatedPrice" class="space-y-3">
                                        <div class="flex justify-between text-slate-300">
                                            <span>Базовая стоимость</span>
                                            <span class="font-bold" x-text="formatPrice(estimatedPrice.base || 0)"></span>
                                        </div>
                                        <div class="flex justify-between text-slate-300" x-show="estimatedPrice.services > 0">
                                            <span>Дополнительные услуги</span>
                                            <span class="font-bold" x-text="formatPrice(estimatedPrice.services || 0)"></span>
                                        </div>
                                        <div class="flex justify-between text-slate-300" x-show="estimatedPrice.distance > 0">
                                            <span>Расстояние</span>
                                            <span class="font-bold" x-text="formatPrice(estimatedPrice.distance || 0)"></span>
                                        </div>
                                        <div class="pt-3 border-t border-amber-500/30">
                                            <div class="flex justify-between items-center">
                                                <span class="text-lg font-bold text-white">Итого</span>
                                                <span class="text-3xl font-black text-amber-400" x-text="formatPrice(estimatedPrice.total || 0)"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div x-show="!estimatedPrice || estimatedPrice.loading" class="text-center py-8">
                                        <svg class="animate-spin h-8 w-8 text-amber-400 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <p class="text-slate-400">Расчет стоимости...</p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-between mt-8">
                                <button @click="currentStep--" class="text-slate-400 hover:text-white px-6 transition">Назад</button>
                                <button @click="submitOrder()" :disabled="isSubmitting" 
                                        class="bg-gradient-to-r from-green-600 to-green-500 hover:from-green-500 hover:to-green-400 text-white font-bold py-3 px-8 rounded-xl disabled:opacity-50 disabled:cursor-not-allowed transition shadow-lg transform hover:scale-105">
                                    <span x-show="!isSubmitting" class="flex items-center">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        Отправить заказ
                                    </span>
                                    <span x-show="isSubmitting" class="flex items-center">
                                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Отправка...
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- МОДАЛЬНОЕ ОКНО: Оценка по фото --}}
    <div x-show="showPhotoEstimate" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/80 z-50 flex items-center justify-center p-4"
         style="display: none;"
         @click.away="closePhotoEstimate()">
        
        <div @click.stop class="bg-slate-900 rounded-3xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto border border-slate-700">
            <div class="sticky top-0 bg-slate-900 border-b border-slate-700 p-6 flex items-center justify-between z-10">
                <h2 class="text-2xl font-bold text-white">Оценка переезда по фото</h2>
                <button @click="closePhotoEstimate()" class="text-slate-400 hover:text-white transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <div class="p-6 space-y-6">
                {{-- Загрузка фотографий --}}
                <div>
                    <label class="block text-sm font-bold text-slate-400 uppercase mb-4">Загрузите фотографии</label>
                    <div class="border-2 border-dashed border-slate-700 rounded-xl p-8 text-center hover:border-amber-500 transition cursor-pointer"
                         @click="$refs.photoInput.click()"
                         @dragover.prevent="photoDragOver = true"
                         @dragleave.prevent="photoDragOver = false"
                         @drop.prevent="handlePhotoDrop($event)"
                         :class="{'border-amber-500 bg-amber-500/10': photoDragOver}">
                        <input type="file" 
                               x-ref="photoInput"
                               @change="handlePhotoSelect($event)"
                               multiple
                               accept="image/*"
                               class="hidden">
                        <div class="space-y-4">
                            <div class="text-6xl">📸</div>
                            <div>
                                <p class="text-white font-semibold mb-2">Перетащите фото сюда или нажмите для выбора</p>
                                <p class="text-slate-400 text-sm">Можно загрузить до 10 фотографий (JPG, PNG, до 10MB каждая)</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Предпросмотр загруженных фото --}}
                <div x-show="photoEstimate.photos.length > 0" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <template x-for="(photo, index) in photoEstimate.photos" :key="index">
                        <div class="relative group">
                            <img :src="photo.preview" class="w-full h-32 object-cover rounded-lg border border-slate-700">
                            <button @click="removePhoto(index)" class="absolute top-2 right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                                ×
                            </button>
                            <div class="absolute bottom-0 left-0 right-0 bg-black/50 text-white text-xs p-1 truncate" x-text="photo.name"></div>
                        </div>
                    </template>
                </div>

                {{-- Результаты оценки --}}
                <div x-show="photoEstimate.result" 
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4"
                     class="bg-slate-800 rounded-xl p-6 border border-amber-500/30"
                     style="display: none;">
                    <h3 class="text-xl font-bold text-amber-400 mb-4 flex items-center">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Предварительная оценка
                    </h3>

                    <div class="grid md:grid-cols-2 gap-4 mb-6">
                        <div class="bg-slate-900 rounded-lg p-4">
                            <p class="text-sm text-slate-400 mb-1">Объем</p>
                            <p class="text-2xl font-bold text-white" x-text="photoEstimate.result?.estimated_volume ? (photoEstimate.result.estimated_volume + ' м³') : '—'"></p>
                        </div>
                        <div class="bg-slate-900 rounded-lg p-4">
                            <p class="text-sm text-slate-400 mb-1">Рекомендуемый тип</p>
                            <p class="text-2xl font-bold text-amber-400 capitalize" x-text="photoEstimate.result?.recommended_package_type || '—'"></p>
                        </div>
                    </div>

                    <div x-show="photoEstimate.result?.items_detected && photoEstimate.result.items_detected.length > 0" class="mb-6">
                        <p class="text-sm font-semibold text-slate-400 mb-2">Обнаружено на фото:</p>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="item in (photoEstimate.result?.items_detected || [])" :key="item">
                                <span class="bg-amber-500/20 text-amber-400 px-3 py-1 rounded-full text-sm" x-text="item"></span>
                            </template>
                        </div>
                    </div>

                    <div class="bg-slate-950 rounded-lg p-6 border border-slate-700">
                        <h4 class="text-lg font-bold text-white mb-4">Смета</h4>
                        <div class="space-y-3">
                            <div class="flex justify-between text-slate-300">
                                <span>Транспорт</span>
                                <span class="font-bold" x-text="(photoEstimate.result?.estimate?.transport_cost || 0) + ' kr'"></span>
                            </div>
                            <div class="flex justify-between text-slate-300">
                                <span>Рабочие пчелы (<span x-text="photoEstimate.result?.estimate?.estimated_hours || 0"></span> ч.)</span>
                                <span class="font-bold" x-text="(photoEstimate.result?.estimate?.labor_cost || 0) + ' kr'"></span>
                            </div>
                            <div class="pt-3 border-t border-slate-700">
                                <div class="flex justify-between">
                                    <span class="text-lg font-bold text-white">Итого</span>
                                    <span class="text-2xl font-black text-amber-500" x-text="(photoEstimate.result?.estimate?.total_cost || 0) + ' kr'"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex gap-4">
                        <button @click="usePhotoEstimate()" class="flex-1 bg-amber-500 hover:bg-amber-400 text-black font-bold py-3 px-6 rounded-xl transition">
                            Использовать эту оценку
                        </button>
                        <button @click="closePhotoEstimate()" class="px-6 py-3 border border-slate-600 text-slate-300 hover:text-white rounded-xl transition">
                            Закрыть
                        </button>
                    </div>
                </div>

                {{-- Progress Bar for Analysis --}}
                <div x-show="photoEstimate.analyzing" class="space-y-2">
                    <div class="bg-slate-800 rounded-full h-3 overflow-hidden">
                        <div class="bg-gradient-to-r from-amber-500 to-orange-500 h-full rounded-full transition-all duration-300"
                             :style="'width: ' + (photoEstimate.progress || 0) + '%'"></div>
                    </div>
                    <p class="text-center text-sm text-slate-400" x-text="photoEstimate.progressText || 'Анализ фотографий...'"></p>
                </div>

                {{-- Кнопка анализа --}}
                <div x-show="photoEstimate.photos.length > 0 && !photoEstimate.result && !photoEstimate.analyzing" class="flex justify-center">
                    <button @click="analyzePhotos()" 
                            class="bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 text-black font-bold py-4 px-10 rounded-xl transition shadow-lg transform hover:scale-105 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Проанализировать фотографии
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function movingPage() {
    return {
        currentStep: 0,
        isSubmitting: false,
        minDate: new Date().toISOString().split('T')[0],
        showPhotoEstimate: false,
        photoDragOver: false,
        showOrderSuccess: false,
        orderSuccessData: null,
        
        photoEstimate: {
            photos: [],
            analyzing: false,
            result: null,
            progress: 0,
            progressText: '',
        },
        estimatedPrice: null,
        
        steps: [
            { label: 'Тип' },
            { label: 'Адреса' },
            { label: 'Услуги' },
            { label: 'Контакты' },
            { label: 'Подтверждение' },
        ],
        
        form: {
            rooms: '1br',
            package_type: 'standard',
            from_address: {
                street: '',
                city: 'Narvik',
                postal_code: '',
                lat: null,
                lng: null,
                floor: null,
                has_elevator: false,
            },
            to_address: {
                street: '',
                city: 'Narvik',
                postal_code: '',
                lat: null,
                lng: null,
                floor: null,
                has_elevator: false,
            },
            services: {
                packing: false,
                assembly: false,
                disassembly: false,
                wrapping: false,
            },
            customer: {
                name: '',
                email: '',
                phone: '',
            },
            scheduled_at: '',
            notes: '',
        },
        
        roomOptions: [
            {id: 'studio', label: 'Студия', icon: '📦'},
            {id: '1br', label: '1 Спальня', icon: '🏠'},
            {id: '2br', label: '2-3 Сп.', icon: '🏘️'},
            {id: 'house', label: 'Дом', icon: '🏡'},
        ],
        
        initPage() {
            // Set default date to tomorrow
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            this.form.scheduled_at = tomorrow.toISOString().split('T')[0];
            
            // Calculate initial price
            this.calculatePrice();
            
            // Watch for form changes to recalculate price
            this.$watch('form', () => {
                if (this.currentStep >= 1) {
                    this.calculatePrice();
                }
            }, { deep: true });
        },
        
        formatPrice(amount) {
            return new Intl.NumberFormat('no-NO', { 
                style: 'currency', 
                currency: 'NOK',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(amount);
        },
        
        async calculatePrice() {
            // Simple price calculation based on form data
            if (!this.form.rooms || !this.form.from_address.street || !this.form.to_address.street) {
                this.estimatedPrice = { loading: true };
                return;
            }
            
            try {
                const basePrices = {
                    'studio': 1500,
                    '1br': 2500,
                    '2br': 4000,
                    'house': 6000,
                };
                
                const packageMultipliers = {
                    'economy': 1.0,
                    'standard': 1.2,
                    'premium': 1.5,
                };
                
                let base = basePrices[this.form.rooms] || 2500;
                let services = 0;
                
                if (this.form.services.packing) services += 500;
                if (this.form.services.assembly) services += 800;
                if (this.form.services.disassembly) services += 600;
                if (this.form.services.wrapping) services += 300;
                
                const distance = this.calculateDistance();
                const distanceCost = Math.max(0, (distance - 5) * 50); // 50 kr per km after 5km
                
                const multiplier = packageMultipliers[this.form.package_type] || 1.2;
                
                const total = (base + services + distanceCost) * multiplier;
                
                this.estimatedPrice = {
                    base: base * multiplier,
                    services: services * multiplier,
                    distance: distanceCost * multiplier,
                    total: Math.round(total),
                    loading: false,
                };
            } catch (error) {
                console.error('Price calculation error:', error);
                this.estimatedPrice = { loading: false, error: true };
            }
        },
        
        nextStep() {
            if (this.currentStep < this.steps.length - 1) {
                this.currentStep++;
            }
        },
        
        scrollToCalculator() {
            document.getElementById('calculator-section').scrollIntoView({ behavior: 'smooth' });
        },
        
        getCurrentLocation(type) {
            if (!navigator.geolocation) {
                if (window.showToast) window.showToast('Геолокация не поддерживается вашим браузером', 'error');
                return;
            }
            
            if (window.showToast) window.showToast('Определение местоположения...', 'info');
            
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    
                    if (type === 'from') {
                        this.form.from_address.lat = lat;
                        this.form.from_address.lng = lng;
                    } else {
                        this.form.to_address.lat = lat;
                        this.form.to_address.lng = lng;
                    }
                    
                    // Reverse geocoding (simplified - in production use a geocoding service)
                    this.reverseGeocode(lat, lng, type);
                    if (window.showToast) window.showToast('Местоположение определено!', 'success');
                },
                (error) => {
                    console.error('Geolocation error:', error);
                    if (window.showToast) window.showToast('Не удалось получить ваше местоположение', 'error');
                }
            );
        },
        
        async reverseGeocode(lat, lng, type) {
            // Simplified reverse geocoding - in production use a proper service
            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`);
                const data = await response.json();
                
                if (data.address) {
                    const addr = data.address;
                    if (type === 'from') {
                        this.form.from_address.street = `${addr.road || ''} ${addr.house_number || ''}`.trim();
                        this.form.from_address.postal_code = addr.postcode || '';
                        this.form.from_address.city = addr.city || addr.town || addr.village || 'Narvik';
                    } else {
                        this.form.to_address.street = `${addr.road || ''} ${addr.house_number || ''}`.trim();
                        this.form.to_address.postal_code = addr.postcode || '';
                        this.form.to_address.city = addr.city || addr.town || addr.village || 'Narvik';
                    }
                }
            } catch (error) {
                console.error('Reverse geocoding error:', error);
            }
        },
        
        async submitOrder() {
            // Validation
            if (!this.form.customer.name || !this.form.customer.email || !this.form.customer.phone) {
                if (window.showToast) window.showToast('Пожалуйста, заполните все контактные данные', 'error');
                this.currentStep = 3;
                return;
            }
            
            if (!this.form.from_address.street || !this.form.to_address.street) {
                if (window.showToast) window.showToast('Пожалуйста, укажите адреса отправления и назначения', 'error');
                this.currentStep = 1;
                return;
            }
            
            if (!this.form.scheduled_at) {
                if (window.showToast) window.showToast('Пожалуйста, выберите дату переезда', 'error');
                this.currentStep = 3;
                return;
            }
            
            // If coordinates are not set, use default Narvik coordinates
            if (!this.form.from_address.lat) {
                this.form.from_address.lat = 68.4372;
                this.form.from_address.lng = 17.4256;
            }
            if (!this.form.to_address.lat) {
                this.form.to_address.lat = 68.4372;
                this.form.to_address.lng = 17.4256;
            }
            
            this.isSubmitting = true;
            
            try {
                // Prepare form data with distance calculation
                const formData = {
                    ...this.form,
                    distance: this.calculateDistance(),
                };
                
                const response = await fetch('/api/v1/moving/orders', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(formData),
                });
                
                const data = await response.json();
                
                if (data.success) {
                    if (window.showToast) {
                        const orderInfo = `Заказ #${data.data.order_number} создан!\nСтоимость: ${this.formatPrice(data.data.estimated_price)}\nРасстояние: ${data.data.distance_km || 'N/A'} км`;
                        window.showToast(orderInfo, 'success');
                    }
                    
                    // Show success modal with order details
                    this.showOrderSuccess(data.data);
                    
                    // Redirect after a delay
                    setTimeout(() => {
                        window.location.href = '/';
                    }, 5000);
                } else {
                    const errorMsg = data.errors ? Object.values(data.errors).flat().join(', ') : (data.message || 'Неизвестная ошибка');
                    if (window.showToast) window.showToast('Ошибка при создании заказа: ' + errorMsg, 'error');
                    console.error('Order creation error:', data);
                }
            } catch (error) {
                console.error('Network error:', error);
                if (window.showToast) window.showToast('Ошибка сети. Пожалуйста, проверьте подключение и попробуйте еще раз.', 'error');
            } finally {
                this.isSubmitting = false;
            }
        },
        
        // Photo Estimate Functions
        openPhotoEstimate() {
            this.showPhotoEstimate = true;
            this.photoEstimate.photos = [];
            this.photoEstimate.result = null;
        },
        
        closePhotoEstimate() {
            this.showPhotoEstimate = false;
            this.photoEstimate.photos = [];
            this.photoEstimate.result = null;
        },
        
        handlePhotoSelect(event) {
            const files = Array.from(event.target.files);
            this.addPhotos(files);
        },
        
        handlePhotoDrop(event) {
            this.photoDragOver = false;
            const files = Array.from(event.dataTransfer.files).filter(file => file.type.startsWith('image/'));
            this.addPhotos(files);
        },
        
        addPhotos(files) {
            if (this.photoEstimate.photos.length + files.length > 10) {
                if (window.showToast) window.showToast('Можно загрузить максимум 10 фотографий', 'warning');
                return;
            }
            
            let added = 0;
            files.forEach(file => {
                if (file.size > 10 * 1024 * 1024) {
                    if (window.showToast) window.showToast(`Файл ${file.name} слишком большой (максимум 10MB)`, 'error');
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.photoEstimate.photos.push({
                        file: file,
                        name: file.name,
                        preview: e.target.result,
                        size: file.size,
                    });
                    added++;
                    if (added === files.length && window.showToast) {
                        window.showToast(`Загружено ${added} фотографий`, 'success');
                    }
                };
                reader.readAsDataURL(file);
            });
        },
        
        removePhoto(index) {
            this.photoEstimate.photos.splice(index, 1);
        },
        
        calculateDistance() {
            // Simple distance calculation (Haversine formula)
            if (!this.form.from_address.lat || !this.form.from_address.lng || 
                !this.form.to_address.lat || !this.form.to_address.lng) {
                return 5; // Default distance in km
            }
            
            const R = 6371; // Earth's radius in km
            const dLat = (this.form.to_address.lat - this.form.from_address.lat) * Math.PI / 180;
            const dLon = (this.form.to_address.lng - this.form.from_address.lng) * Math.PI / 180;
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                      Math.cos(this.form.from_address.lat * Math.PI / 180) * 
                      Math.cos(this.form.to_address.lat * Math.PI / 180) *
                      Math.sin(dLon/2) * Math.sin(dLon/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return Math.round(R * c * 10) / 10; // Round to 1 decimal
        },
        
        async analyzePhotos() {
            if (this.photoEstimate.photos.length === 0) {
                if (window.showToast) window.showToast('Пожалуйста, загрузите хотя бы одну фотографию', 'warning');
                return;
            }
            
            this.photoEstimate.analyzing = true;
            this.photoEstimate.result = null;
            this.photoEstimate.progress = 0;
            this.photoEstimate.progressText = 'Загрузка фотографий...';
            
            // Simulate progress
            const progressInterval = setInterval(() => {
                if (this.photoEstimate.progress < 90) {
                    this.photoEstimate.progress += 10;
                    if (this.photoEstimate.progress < 30) {
                        this.photoEstimate.progressText = 'Загрузка фотографий...';
                    } else if (this.photoEstimate.progress < 60) {
                        this.photoEstimate.progressText = 'Анализ изображений...';
                    } else if (this.photoEstimate.progress < 90) {
                        this.photoEstimate.progressText = 'Расчет стоимости...';
                    }
                }
            }, 300);
            
            try {
                const formData = new FormData();
                this.photoEstimate.photos.forEach((photo) => {
                    formData.append('photos[]', photo.file);
                });
                
                const response = await fetch('/api/v1/moving/photo-estimate', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Accept': 'application/json',
                    },
                    body: formData,
                });
                
                clearInterval(progressInterval);
                this.photoEstimate.progress = 100;
                this.photoEstimate.progressText = 'Завершено!';
                
                const data = await response.json();
                
                if (data.success && data.data) {
                    this.photoEstimate.result = {
                        estimated_volume: data.data.estimated_volume || 0,
                        recommended_package_type: data.data.recommended_package_type || 'standard',
                        items_detected: data.data.items_detected || [],
                        estimate: {
                            transport_cost: data.data.estimate?.transport_cost || 0,
                            labor_cost: data.data.estimate?.labor_cost || 0,
                            total_cost: data.data.estimate?.total_cost || 0,
                            estimated_hours: data.data.estimate?.estimated_hours || 0,
                        }
                    };
                    if (window.showToast) window.showToast('Анализ завершен успешно!', 'success');
                } else {
                    this.photoEstimate.result = null;
                    const errorMsg = data.errors ? Object.values(data.errors).flat().join(', ') : (data.message || 'Неизвестная ошибка');
                    if (window.showToast) window.showToast('Ошибка при анализе фотографий: ' + errorMsg, 'error');
                }
            } catch (error) {
                clearInterval(progressInterval);
                console.error('Photo analysis error:', error);
                if (window.showToast) window.showToast('Ошибка сети. Пожалуйста, проверьте подключение и попробуйте еще раз.', 'error');
            } finally {
                setTimeout(() => {
                    this.photoEstimate.analyzing = false;
                    this.photoEstimate.progress = 0;
                    this.photoEstimate.progressText = '';
                }, 500);
            }
        },
        
        usePhotoEstimate() {
            if (!this.photoEstimate.result || !this.photoEstimate.result.estimated_volume) {
                if (window.showToast) window.showToast('Нет данных для применения', 'error');
                return;
            }
            
            // Применить результаты оценки к форме
            const volume = this.photoEstimate.result.estimated_volume || 0;
            
            // Определить тип переезда на основе объема
            if (volume < 10) {
                this.form.rooms = 'studio';
            } else if (volume < 20) {
                this.form.rooms = '1br';
            } else if (volume < 35) {
                this.form.rooms = '2br';
            } else {
                this.form.rooms = 'house';
            }
            
            // Применить рекомендуемый тип упаковки
            if (this.photoEstimate.result.recommended_package_type) {
                this.form.package_type = this.photoEstimate.result.recommended_package_type;
            }
            
            // Если обнаружены предметы, требующие упаковки, включить услугу
            if (this.photoEstimate.result.items_detected && 
                Array.isArray(this.photoEstimate.result.items_detected) &&
                this.photoEstimate.result.items_detected.some(item => 
                    item.includes('Мебель') || item.includes('техника') || item.includes('хрупк')
                )) {
                this.form.services.packing = true;
            }
            
            if (window.showToast) window.showToast('Данные применены к форме', 'success');
            
            // Закрыть модальное окно и перейти к форме
            this.closePhotoEstimate();
            this.currentStep = 0;
            this.scrollToCalculator();
        },
        
        showOrderSuccess(data) {
            this.orderSuccessData = data;
            this.showOrderSuccess = true;
        },
        
        closeOrderSuccess() {
            this.showOrderSuccess = false;
            this.orderSuccessData = null;
        }
    }
}
</script>

{{-- Success Modal --}}
<div x-show="showOrderSuccess" 
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 bg-black/80 z-[9998] flex items-center justify-center p-4"
     style="display: none;"
     @click.away="closeOrderSuccess()">
    
    <div @click.stop 
         x-show="showOrderSuccess"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-3xl shadow-2xl max-w-md w-full border-4 border-white/20">
        <div class="p-8 text-center text-white">
            <div class="mb-6">
                <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h2 class="text-3xl font-black mb-2">Заказ создан!</h2>
                <p class="text-green-100">Ваш заказ успешно оформлен</p>
            </div>
            
            <div x-show="orderSuccessData" class="bg-white/10 backdrop-blur-sm rounded-xl p-6 mb-6 space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-green-100">Номер заказа:</span>
                    <span class="font-bold text-xl" x-text="orderSuccessData?.order_number"></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-green-100">Стоимость:</span>
                    <span class="font-bold text-xl" x-text="formatPrice(orderSuccessData?.estimated_price || 0)"></span>
                </div>
                <div x-show="orderSuccessData?.distance_km" class="flex justify-between items-center">
                    <span class="text-green-100">Расстояние:</span>
                    <span class="font-bold" x-text="orderSuccessData?.distance_km + ' км'"></span>
                </div>
                <div x-show="orderSuccessData?.scheduled_at" class="flex justify-between items-center">
                    <span class="text-green-100">Дата:</span>
                    <span class="font-bold" x-text="new Date(orderSuccessData?.scheduled_at).toLocaleDateString('ru-RU')"></span>
                </div>
            </div>
            
            <div class="flex gap-4">
                <button @click="closeOrderSuccess()" 
                        class="flex-1 bg-white/20 hover:bg-white/30 text-white font-bold py-3 px-6 rounded-xl transition backdrop-blur-sm">
                    Закрыть
                </button>
                <button @click="window.location.href = '/'" 
                        class="flex-1 bg-white text-green-600 font-bold py-3 px-6 rounded-xl hover:bg-green-50 transition">
                    На главную
                </button>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes float {
    0%, 100% { transform: translateY(0px) rotate(0deg); }
    50% { transform: translateY(-20px) rotate(5deg); }
}

@keyframes fade-in {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes fade-in-up {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes gradient {
    0%, 100% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
}

.animate-float {
    animation: float 6s ease-in-out infinite;
}

.animate-fade-in {
    animation: fade-in 1s ease-out;
}

.animate-fade-in-up {
    animation: fade-in-up 0.8s ease-out forwards;
    opacity: 0;
}

.animate-gradient {
    background-size: 200% 200%;
    animation: gradient 3s ease infinite;
}

.animate-pulse-slow {
    animation: pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
@endsection
