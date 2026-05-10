@extends('layouts.app')

@section('content')
<div x-data="errandPage()" class="min-h-screen bg-violet-50 font-sans">

    {{-- 1. HERO SECTION: Ваше время бесценно --}}
    <div class="relative bg-violet-900 h-[450px] overflow-hidden">
        <div class="absolute inset-0 opacity-40">
            {{-- Фото: Человек отдыхает или пьет кофе, пока другие работают --}}
            <img src="https://images.unsplash.com/photo-1483389127117-b6a2102724ae?auto=format&fit=crop&w=2070&q=80" class="w-full h-full object-cover">
        </div>
        <div class="relative container mx-auto px-4 h-full flex flex-col justify-center items-center text-center z-10">
            <span class="bg-white/20 backdrop-blur-sm border border-white/30 text-white text-xs font-bold px-4 py-1.5 rounded-full uppercase tracking-widest mb-6">Alle Concierge</span>
            <h1 class="text-4xl md:text-6xl font-bold text-white mb-6 leading-tight">
                Ваш личный помощник<br>в Нарвике
            </h1>
            <p class="text-xl text-violet-100 mb-8 max-w-2xl mx-auto">
                Сходить в аптеку, забрать посылку, постоять в очереди или купить подарок. 
                Делегируйте рутину нам, а сами наслаждайтесь жизнью.
            </p>
        </div>
        <div class="absolute bottom-0 left-0 right-0 text-violet-50">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 120" class="w-full h-auto"><path fill="currentColor" fill-opacity="1" d="M0,64L80,69.3C160,75,320,85,480,80C640,75,800,53,960,48C1120,43,1280,53,1360,58.7L1440,64L1440,320L1360,320C1280,320,1120,320,960,320C800,320,640,320,480,320C320,320,160,320,80,320L0,320Z"></path></svg>
        </div>
    </div>

    <div class="container mx-auto px-4 -mt-20 relative z-20 mb-24">
        <div class="flex flex-col lg:flex-row gap-8">

            {{-- 2. ЛЕВАЯ КОЛОНКА: Форма задачи --}}
            <div class="w-full lg:w-2/3">
                
                <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
                    <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">Популярные поручения</h3>
                    <div class="flex flex-wrap gap-3">
                        <button @click="setScenario('pharmacy')" class="px-4 py-2 rounded-full bg-violet-50 text-violet-700 hover:bg-violet-100 transition flex items-center text-sm font-medium">
                            💊 Аптека
                        </button>
                        <button @click="setScenario('post')" class="px-4 py-2 rounded-full bg-violet-50 text-violet-700 hover:bg-violet-100 transition flex items-center text-sm font-medium">
                            📦 Забрать посылку
                        </button>
                        <button @click="setScenario('gift')" class="px-4 py-2 rounded-full bg-violet-50 text-violet-700 hover:bg-violet-100 transition flex items-center text-sm font-medium">
                            🎁 Купить подарок
                        </button>
                        <button @click="setScenario('keys')" class="px-4 py-2 rounded-full bg-violet-50 text-violet-700 hover:bg-violet-100 transition flex items-center text-sm font-medium">
                            🔑 Передать ключи
                        </button>
                        <button @click="setScenario('pet')" class="px-4 py-2 rounded-full bg-violet-50 text-violet-700 hover:bg-violet-100 transition flex items-center text-sm font-medium">
                            🐕 Выгул собаки
                        </button>
                    </div>
                </div>

                <div class="bg-white rounded-3xl shadow-xl p-8 md:p-10 border border-violet-100">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Опишите задачу</h2>

                    <div class="mb-8">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Что нужно сделать?</label>
                        <textarea x-model="description" rows="4" class="w-full p-4 border-gray-200 rounded-xl bg-gray-50 focus:bg-white focus:border-violet-500 focus:ring-violet-500 transition" placeholder="Например: Зайти в аптеку Vitusapotek в AMFI, купить парацетамол и витамины, привезти на адрес..." maxlength="500"></textarea>
                        <p class="text-xs text-gray-400 mt-2 text-right" x-text="description.length + '/500'"></p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Откуда / Где купить</label>
                            <div class="relative">
                                <span class="absolute left-3 top-3.5 text-gray-400">📍</span>
                                <input type="text" x-model="locationFrom" class="w-full pl-10 p-3 border-gray-200 rounded-lg focus:border-violet-500 focus:ring-violet-500" placeholder="Адрес или название места">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Куда доставить (Если нужно)</label>
                            <div class="relative">
                                <span class="absolute left-3 top-3.5 text-gray-400">🏠</span>
                                <input type="text" x-model="locationTo" class="w-full pl-10 p-3 border-gray-200 rounded-lg focus:border-violet-500 focus:ring-violet-500" placeholder="Ваш адрес">
                            </div>
                        </div>
                    </div>

                    <div class="mb-8 bg-violet-50/50 rounded-xl p-6 border border-violet-100">
                        <label class="block text-sm font-bold text-gray-700 mb-4 flex justify-between">
                            <span>Сколько времени это займет? (Оценка)</span>
                            <span class="text-violet-700 font-bold" x-text="duration + ' час(а)'"></span>
                        </label>
                        <input type="range" min="0.5" max="5" step="0.5" x-model="duration" class="w-full h-2 bg-violet-200 rounded-lg appearance-none cursor-pointer accent-violet-600 mb-6">
                        
                        <div class="flex items-center justify-between">
                            <div class="w-full mr-4">
                                <label class="block text-sm font-bold text-gray-700 mb-2">Бюджет на покупки (kr)</label>
                                <input type="number" x-model="purchaseBudget" placeholder="0" class="w-full p-3 border-gray-200 rounded-lg focus:border-violet-500 focus:ring-violet-500">
                                <p class="text-xs text-gray-500 mt-1">Если нужно что-то купить. Оплата по чеку.</p>
                            </div>
                            <div class="flex items-center h-full pt-6">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" x-model="isUrgent" class="w-5 h-5 text-violet-600 rounded border-gray-300 focus:ring-violet-500">
                                    <span class="ml-2 text-sm font-medium text-gray-700">Срочно (ASAP)</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-8">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Ваши контактные данные</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Имя</label>
                                <input type="text" x-model="customerName" class="w-full p-3 border-gray-200 rounded-lg focus:border-violet-500 focus:ring-violet-500" placeholder="Ваше имя" required>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Телефон</label>
                                <input type="tel" x-model="customerPhone" class="w-full p-3 border-gray-200 rounded-lg focus:border-violet-500 focus:ring-violet-500" placeholder="+47 XXX XX XXX" required>
                            </div>
                        </div>
                        <div class="mt-4">
                            <label class="block text-sm font-bold text-gray-700 mb-2">Email</label>
                            <input type="email" x-model="customerEmail" class="w-full p-3 border-gray-200 rounded-lg focus:border-violet-500 focus:ring-violet-500" placeholder="your@email.com" required>
                        </div>
                    </div>

                </div>
            </div>

            {{-- 3. ПРАВАЯ КОЛОНКА: Чек (Sticky) --}}
            <div class="w-full lg:w-1/3">
                <div class="sticky top-8">
                    <div class="bg-white rounded-3xl shadow-2xl p-8 border border-gray-100 relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-violet-500 rounded-full opacity-5 -mr-10 -mt-10 blur-2xl"></div>

                        <h3 class="text-xl font-bold text-gray-800 mb-6">Итоговая смета</h3>

                        <div class="space-y-4 mb-8 text-sm">
                            <div class="flex justify-between text-gray-600">
                                <span>Базовый выезд помощника</span>
                                <span>299 kr</span>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>Время (<span x-text="duration"></span> ч. x 450kr)</span>
                                <span x-text="(duration * 450) + ' kr'"></span>
                            </div>
                            <div x-show="isUrgent" class="flex justify-between text-violet-600 font-medium">
                                <span>Срочность (+30%)</span>
                                <span x-text="urgentFee + ' kr'"></span>
                            </div>
                            <div x-show="purchaseBudget > 0" class="flex justify-between text-gray-400 border-t border-dashed border-gray-200 pt-2 mt-2">
                                <span>Бюджет покупок (Ориентир)</span>
                                <span x-text="purchaseBudget + ' kr'"></span>
                            </div>
                        </div>

                        <div class="border-t border-gray-100 pt-6 mb-6">
                            <div class="flex justify-between items-end">
                                <span class="text-gray-500 font-medium">К оплате за сервис:</span>
                                <span class="text-3xl font-bold text-violet-600" x-text="totalPrice + ' kr'"></span>
                            </div>
                            <p class="text-xs text-gray-400 mt-2 text-right">*Покупки оплачиваются отдельно по чеку</p>
                        </div>

                        <button @click="submitOrder()" :disabled="isSubmitting" class="w-full bg-violet-600 hover:bg-violet-700 disabled:bg-gray-400 text-white font-bold py-4 rounded-xl shadow-lg shadow-violet-200 transition transform active:scale-95 flex justify-center items-center group">
                            <span x-show="!isSubmitting">Найти помощника</span>
                            <span x-show="isSubmitting" x-text="submitText"></span>
                            <svg x-show="!isSubmitting" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 ml-2 group-hover:translate-x-1 transition"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                        </button>
                        
                        <div class="mt-6 flex justify-center space-x-4 grayscale opacity-50">
                            <div class="h-6 w-10 bg-gray-200 rounded"></div>
                            <div class="h-6 w-10 bg-gray-200 rounded"></div>
                            <div class="h-6 w-10 bg-gray-200 rounded"></div>
                        </div>
                    </div>

                    <div class="mt-6 bg-violet-100 rounded-xl p-4 flex items-start">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-violet-600 mr-3 flex-shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <div>
                            <h4 class="font-bold text-violet-900 text-sm">Безопасная сделка</h4>
                            <p class="text-xs text-violet-700 mt-1">Деньги резервируются, но списываются только после выполнения задачи. Исполнители проверены по BankID.</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>

<script>
function errandPage() {
    return {
        description: '',
        locationFrom: '',
        locationTo: '',
        duration: 1,
        purchaseBudget: '',
        isUrgent: false,
        customerName: '',
        customerPhone: '',
        customerEmail: '',
        isSubmitting: false,
        submitText: 'Отправка...',

        baseFee: 299,
        hourlyRate: 450,

        setScenario(type) {
            const scenarios = {
                'pharmacy': { text: 'Купить лекарства в аптеке. Список: ...', from: 'Vitusapotek AMFI', dur: 1 },
                'post': { text: 'Забрать посылку с почты. Трек-номер: ...', from: 'Post i Butikk (Rema 1000)', dur: 0.5 },
                'gift': { text: 'Купить подарок на день рождения (Цветы и конфеты)', from: 'Narvik Storsenter', dur: 1.5 },
                'keys': { text: 'Забрать ключи и передать гостю', from: '', dur: 0.5 },
                'pet': { text: 'Выгулять собаку в парке', from: 'Мой адрес', dur: 1 }
            };
            
            if(scenarios[type]) {
                this.description = scenarios[type].text;
                this.locationFrom = scenarios[type].from;
                this.duration = scenarios[type].dur;
                // Скролл к форме
                window.scrollTo({ top: 400, behavior: 'smooth' });
            }
        },

        get urgentFee() {
            if (!this.isUrgent) return 0;
            return Math.round((this.baseFee + (this.duration * this.hourlyRate)) * 0.3);
        },

        async submitOrder() {
            if (!this.customerName || !this.customerPhone || !this.customerEmail || !this.description) {
                alert('Пожалуйста, заполните все обязательные поля');
                return;
            }

            this.isSubmitting = true;
            this.submitText = 'Создание заказа...';

            try {
                const response = await fetch('/api/v1/errand/orders', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        description: this.description,
                        location_from: this.locationFrom,
                        location_to: this.locationTo,
                        duration: this.duration,
                        purchase_budget: this.purchaseBudget || 0,
                        is_urgent: this.isUrgent,
                        customer_name: this.customerName,
                        customer_phone: this.customerPhone,
                        customer_email: this.customerEmail,
                        total_amount: this.totalPrice,
                    })
                });

                const data = await response.json();

                if (response.ok) {
                    this.submitText = 'Заказ создан!';
                    // Redirect to order status page or show success message
                    setTimeout(() => {
                        window.location.href = `/order/${data.order_number}`;
                    }, 1000);
                } else {
                    throw new Error(data.message || 'Ошибка при создании заказа');
                }
            } catch (error) {
                console.error('Order submission error:', error);
                this.submitText = 'Ошибка. Попробуйте снова.';
                alert('Произошла ошибка при создании заказа. Пожалуйста, попробуйте снова.');
            } finally {
                setTimeout(() => {
                    this.isSubmitting = false;
                    this.submitText = 'Отправка...';
                }, 2000);
            }
        },

        get totalPrice() {
            const serviceCost = this.baseFee + (this.duration * this.hourlyRate);
            return serviceCost + this.urgentFee;
        }
    }
}
</script>
@endsection

