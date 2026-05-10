@extends('layouts.app')

@section('content')
<div x-data="handymanPage()" x-init="initPage()" class="min-h-screen bg-gray-50 font-sans">

    {{-- 1. HERO SECTION: Профессионализм и доверие --}}
    <div class="relative bg-slate-900 h-[500px] overflow-hidden">
        <div class="absolute inset-0 opacity-60">
            {{-- Фото мастера за работой --}}
            <img src="https://images.unsplash.com/photo-1621905251189-08b45d6a269e?auto=format&fit=crop&w=2070&q=80" class="w-full h-full object-cover">
        </div>
        <div class="relative container mx-auto px-4 h-full flex flex-col justify-center items-start z-10">
            <div class="bg-amber-500 text-slate-900 text-xs font-bold px-3 py-1 rounded uppercase tracking-wider mb-4">Alle Master</div>
            <h1 class="text-5xl md:text-6xl font-bold text-white mb-6 leading-tight">
                Ваш личный мастер<br>в Нарвике
            </h1>
            <p class="text-xl text-gray-200 mb-8 max-w-xl">
                Сборка мебели, мелкий ремонт, монтаж и установка техники. 
                Профессиональные инструменты и гарантия на работу.
            </p>
            <button @click="scrollToCalculator()" class="bg-amber-500 text-slate-900 font-bold py-4 px-8 rounded-lg hover:bg-amber-400 transition shadow-lg transform hover:-translate-y-1">
                Рассчитать стоимость и заказать
            </button>
        </div>
    </div>

    {{-- 2. КАТЕГОРИИ УСЛУГ (Grid) --}}
    <div class="container mx-auto px-4 -mt-20 relative z-20 mb-16">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <div @click="selectServiceType('furniture')" 
                 :class="{'ring-4 ring-amber-500 translate-y-[-5px]': serviceType === 'furniture'}"
                 class="bg-white rounded-xl shadow-xl p-8 cursor-pointer transition-all hover:shadow-2xl group">
                <div class="w-14 h-14 bg-amber-100 rounded-lg flex items-center justify-center mb-6 group-hover:bg-amber-500 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-amber-700 group-hover:text-white">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-slate-800 mb-2">Сборка мебели</h3>
                <p class="text-slate-500 text-sm">IKEA, Skeidar, Bohus. Шкафы, кровати, кухни.</p>
            </div>

            <div @click="selectServiceType('mounting')"
                 :class="{'ring-4 ring-amber-500 translate-y-[-5px]': serviceType === 'mounting'}"
                 class="bg-white rounded-xl shadow-xl p-8 cursor-pointer transition-all hover:shadow-2xl group">
                <div class="w-14 h-14 bg-blue-100 rounded-lg flex items-center justify-center mb-6 group-hover:bg-blue-500 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-blue-700 group-hover:text-white">
                         <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.702-.127 1.297.146 2.45.896 2.853 1.948a1.107 1.107 0 00-1.377-.362M9.375 19.5L3 16.875" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-slate-800 mb-2">Монтаж и сверление</h3>
                <p class="text-slate-500 text-sm">Полки, карнизы, картины, кронштейны для ТВ.</p>
            </div>

            <div @click="selectServiceType('repair')"
                 :class="{'ring-4 ring-amber-500 translate-y-[-5px]': serviceType === 'repair'}"
                 class="bg-white rounded-xl shadow-xl p-8 cursor-pointer transition-all hover:shadow-2xl group">
                <div class="w-14 h-14 bg-slate-100 rounded-lg flex items-center justify-center mb-6 group-hover:bg-slate-600 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-slate-700 group-hover:text-white">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75a4.5 4.5 0 01-4.884 4.484c-1.076-.091-2.264.071-2.95.904l-7.152 8.684a2.548 2.548 0 11-3.586-3.586l8.684-7.152c.833-.686.995-1.874.904-2.95a4.5 4.5 0 016.336-4.486l-3.276 3.276a3.004 3.004 0 002.25 2.25l3.276-3.276c.256.565.398 1.192.398 1.852z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.867 19.125h.008v.008h-.008v-.008z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-slate-800 mb-2">Мелкий ремонт</h3>
                <p class="text-slate-500 text-sm">Замена ручек, ремонт дверей, сантехнические мелочи.</p>
            </div>
        </div>
    </div>

    {{-- 3. УМНЫЙ КАЛЬКУЛЯТОР --}}
    <div id="calculator-section" class="container mx-auto px-4 mb-20">
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden flex flex-col md:flex-row">
            
            <div class="w-full md:w-2/3 p-8 md:p-12">
                <h2 class="text-2xl font-bold text-slate-900 mb-6 flex items-center">
                    <span class="bg-amber-500 w-2 h-8 mr-3 rounded-full"></span>
                    Рассчитать стоимость работ
                </h2>
                
                <div class="mb-8">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Что нужно сделать?</label>
                    <select x-model="selectedTask" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500 bg-white">
                        <template x-for="task in availableTasks" :key="task.id">
                            <option :value="task.id" x-text="task.name"></option>
                        </template>
                    </select>
                    <p class="text-xs text-gray-500 mt-2" x-text="getCurrentTaskDesc()"></p>
                </div>

                <div class="mb-8">
                    <div class="flex justify-between mb-2">
                        <label class="block text-sm font-bold text-gray-700">Сколько времени это займет?</label>
                        <span class="font-bold text-amber-600" x-text="hours + ' ч.'"></span>
                    </div>
                    <input type="range" min="1" max="8" step="0.5" x-model="hours" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-amber-500">
                    <div class="flex justify-between text-xs text-gray-400 mt-2">
                        <span>1 час</span>
                        <span>4 часа</span>
                        <span>8 часов (День)</span>
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" x-model="hasMaterials" class="form-checkbox h-5 w-5 text-amber-600 rounded border-gray-300 focus:ring-amber-500">
                        <span class="ml-2 text-gray-700">Нужно купить материалы</span>
                    </label>
                </div>
            </div>

            <div class="w-full md:w-1/3 bg-slate-900 text-white p-8 md:p-12 flex flex-col justify-between">
                <div>
                    <h3 class="text-lg font-medium text-gray-400 mb-4">Предварительная смета</h3>
                    
                    <div class="flex justify-between mb-2 text-sm">
                        <span>Выезд мастера</span>
                        <span x-text="baseFee + ' kr'"></span>
                    </div>
                    <div class="flex justify-between mb-2 text-sm">
                        <span>Работа (<span x-text="hours"></span> ч.)</span>
                        <span x-text="workCost + ' kr'"></span>
                    </div>
                    <div x-show="hasMaterials" class="flex justify-between mb-2 text-sm text-amber-400">
                        <span>Покупка материалов</span>
                        <span>+250 kr</span>
                    </div>
                    
                    <div class="h-px bg-gray-700 my-4"></div>
                    
                    <div class="flex justify-between items-end">
                        <span class="text-xl font-bold">Итого:</span>
                        <span class="text-4xl font-bold text-amber-500" x-text="totalPrice + ' kr'"></span>
                    </div>
                    <p class="text-xs text-gray-500 mt-2 text-right">*Цена включает НДС</p>
                </div>

                <button class="mt-8 w-full bg-amber-500 text-slate-900 font-bold py-3 rounded-lg hover:bg-amber-400 transition">
                    Вызвать мастера
                </button>
            </div>
        </div>
    </div>

    {{-- 4. ПОЧЕМУ МЫ (Trust Blocks) --}}
    <div class="container mx-auto px-4 mb-20">
        <h2 class="text-3xl font-bold text-center mb-12">Стандарты Alle Master</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center p-6">
                <div class="w-16 h-16 mx-auto bg-green-100 rounded-full flex items-center justify-center mb-4 text-green-600">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <h4 class="font-bold text-lg mb-2">Проверенные профи</h4>
                <p class="text-gray-500">Все мастера проходят проверку личности и навыков. Рейтинг на основе отзывов.</p>
            </div>
            <div class="text-center p-6">
                <div class="w-16 h-16 mx-auto bg-blue-100 rounded-full flex items-center justify-center mb-4 text-blue-600">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <h4 class="font-bold text-lg mb-2">Точно в срок</h4>
                <p class="text-gray-500">Мастер приедет в выбранный вами часовой слот. Никаких "ожидайте в течение дня".</p>
            </div>
            <div class="text-center p-6">
                <div class="w-16 h-16 mx-auto bg-purple-100 rounded-full flex items-center justify-center mb-4 text-purple-600">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8"><path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.702-.127 1.297.146 2.45.896 2.853 1.948a1.107 1.107 0 00-1.377-.362M9.375 19.5L3 16.875" /></svg>
                </div>
                <h4 class="font-bold text-lg mb-2">Свои инструменты</h4>
                <p class="text-gray-500">Вам не нужно искать дрель или ключи. Мастер приезжает полностью экипированным.</p>
            </div>
        </div>
    </div>

</div>

<script>
function handymanPage() {
    return {
        serviceType: 'furniture', // furniture, mounting, repair
        selectedTask: 1,
        hours: 2,
        baseFee: 499,
        hourlyRate: 750,
        hasMaterials: false,
        
        // Пример данных (позже заменить на fetch /api/v1/service-types?category=handyman)
        tasks: {
            furniture: [
                {id: 1, name: 'Сборка шкафа (PAX/Аналоги)', price: 750},
                {id: 2, name: 'Сборка кровати', price: 750},
                {id: 3, name: 'Сборка комода/тумбы', price: 650},
            ],
            mounting: [
                {id: 10, name: 'Установка полок (до 3 шт)', price: 800},
                {id: 11, name: 'Монтаж карниза', price: 800},
                {id: 12, name: 'Установка кронштейна ТВ', price: 850},
            ],
            repair: [
                {id: 20, name: 'Ремонт дверной ручки/замка', price: 900},
                {id: 21, name: 'Замена смесителя', price: 950},
                {id: 22, name: 'Другой мелкий ремонт', price: 850},
            ]
        },

        get availableTasks() {
            return this.tasks[this.serviceType] || [];
        },
        
        get workCost() {
            // Находим цену часа для выбранной задачи
            let currentTask = this.availableTasks.find(t => t.id == this.selectedTask);
            let rate = currentTask ? currentTask.price : this.hourlyRate;
            return rate * this.hours;
        },

        get totalPrice() {
            let materialFee = this.hasMaterials ? 250 : 0;
            return this.baseFee + this.workCost + materialFee;
        },

        initPage() {
            // Можно здесь добавить fetch к API
        },

        selectServiceType(type) {
            this.serviceType = type;
            // Выбираем первую задачу из списка новой категории
            if (this.tasks[type] && this.tasks[type].length > 0) {
                this.selectedTask = this.tasks[type][0].id;
            }
        },
        
        getCurrentTaskDesc() {
            let currentTask = this.availableTasks.find(t => t.id == this.selectedTask);
            return currentTask ? `Тариф: ${currentTask.price} kr/час` : '';
        },

        scrollToCalculator() {
            document.getElementById('calculator-section').scrollIntoView({ behavior: 'smooth' });
        }
    }
}
</script>
@endsection

