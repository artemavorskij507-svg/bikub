@extends('layouts.app')

@section('content')
<div x-data="socialPage()" x-init="initPage()" class="min-h-screen bg-slate-950 text-white font-sans selection:bg-amber-500 selection:text-black">

    {{-- 1. HERO SECTION (Без изменений, так как дизайн хороший) --}}
    <div class="relative h-[600px] overflow-hidden">
        <div class="absolute inset-0">
            <img src="https://images.unsplash.com/photo-1581579438747-1dc8d17bbce4?auto=format&fit=crop&w=2070&q=80" class="w-full h-full object-cover object-center opacity-50">
            <div class="absolute inset-0 bg-gradient-to-r from-slate-950 via-slate-900/70 to-orange-900/20"></div>
            <div class="absolute bottom-0 left-0 w-full h-32 bg-gradient-to-t from-slate-950 to-transparent"></div>
        </div>
        
        <div class="absolute inset-0 opacity-10 pointer-events-none mix-blend-overlay" 
             style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cpath d=\'M30 0l25.98 15v30L30 60 4.02 45V15z\' fill-rule=\'evenodd\' stroke=\'%23fbbf24\' fill=\'none\'/%3E%3C/svg%3E');">
        </div>

        <div class="relative container mx-auto px-4 h-full flex items-center z-10">
            <div class="max-w-3xl">
                <div class="inline-flex items-center space-x-2 mb-6 animate-fade-in-down">
                    <span class="bg-amber-500 text-black text-xs font-bold px-3 py-1 rounded-full uppercase tracking-widest flex items-center">
                        <span class="mr-1">🐝</span> BiKube Care
                    </span>
                    <span class="border border-amber-500/50 text-amber-500 text-xs px-3 py-1 rounded-full uppercase tracking-widest">
                        Social Module v1.0
                    </span>
                </div>
                
                <h1 class="text-5xl md:text-7xl font-black mb-6 leading-tight drop-shadow-xl">
                    Ваша личная<br>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-300 via-amber-500 to-orange-500">Пчела-Заботы.</span>
                </h1>
                
                <p class="text-xl text-amber-100/90 mb-10 max-w-2xl font-light leading-relaxed">
                    Профессиональный социальный уход в Нарвике. Мы подберем помощника, который станет другом семьи.
                </p>
                
                <button @click="scrollToWizard()" class="bg-amber-500 text-black font-bold py-4 px-10 rounded-full hover:bg-amber-400 transition shadow-[0_0_30px_rgba(245,158,11,0.4)] flex items-center">
                    Оформить заявку на уход
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 ml-2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </button>
            </div>
        </div>
    </div>

    {{-- 2. WIZARD SECTION (Основная форма интеграции) --}}
    <div id="care-wizard" class="container mx-auto px-4 -mt-20 relative z-20 mb-24">
        <div class="bg-slate-900 rounded-3xl shadow-2xl border border-slate-800 overflow-hidden flex flex-col lg:flex-row min-h-[600px]">
            
            <div class="w-full lg:w-1/4 bg-slate-950 p-8 border-r border-slate-800">
                <h3 class="text-amber-500 font-bold uppercase tracking-widest text-xs mb-8">Конфигуратор Заботы</h3>
                
                <div class="space-y-6 relative">
                    <div class="absolute left-3.5 top-2 bottom-2 w-0.5 bg-slate-800 z-0"></div>
                    <div class="absolute left-3.5 top-2 w-0.5 bg-amber-500 z-0 transition-all duration-500" :style="`height: ${(step-1) * 25}%`"></div>

                    <template x-for="(label, index) in steps" :key="index">
                        <div class="relative z-10 flex items-center">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold border-2 transition-all duration-300"
                                 :class="step > index + 1 ? 'bg-amber-500 border-amber-500 text-black' : (step === index + 1 ? 'bg-slate-900 border-amber-500 text-amber-500' : 'bg-slate-900 border-slate-700 text-slate-500')">
                                <span x-show="step <= index + 1" x-text="index + 1"></span>
                                <span x-show="step > index + 1">✓</span>
                            </div>
                            <span class="ml-4 text-sm font-medium transition-colors"
                                  :class="step === index + 1 ? 'text-white' : 'text-slate-500'" x-text="label"></span>
                        </div>
                    </template>
                </div>

                <div class="mt-12 p-4 bg-slate-800/50 rounded-xl border border-slate-700 text-xs text-slate-400">
                    <h4 class="text-white font-bold mb-2">Предварительно:</h4>
                    <div x-show="form.service_type" class="flex justify-between mb-1">
                        <span>Услуга:</span> <span class="text-amber-500" x-text="form.service_type"></span>
                    </div>
                     <div x-show="form.frequency" class="flex justify-between mb-1">
                        <span>График:</span> <span class="text-amber-500" x-text="form.frequency"></span>
                    </div>
                </div>
            </div>

            <div class="w-full lg:w-3/4 p-8 lg:p-12 bg-slate-900 relative">
                
                {{-- STEP 1: SERVICE TYPE --}}
                <div x-show="step === 1" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-4">
                    <h2 class="text-3xl font-bold mb-2">Что требуется?</h2>
                    <p class="text-slate-400 mb-8">Выберите основной тип помощи. Это определит квалификацию помощника.</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div @click="form.service_type = 'Компаньон'; nextStep()" class="cursor-pointer bg-slate-800 p-6 rounded-xl border border-slate-700 hover:border-amber-500 hover:shadow-[0_0_20px_rgba(245,158,11,0.2)] transition group">
                            <div class="text-4xl mb-3 group-hover:scale-110 transition">☕</div>
                            <h3 class="font-bold text-lg text-white">Компаньон</h3>
                            <p class="text-sm text-slate-400 mt-1">Общение, прогулки, чтение, совместный досуг. Без медицинской помощи.</p>
                        </div>
                        <div @click="form.service_type = 'Бытовая помощь'; nextStep()" class="cursor-pointer bg-slate-800 p-6 rounded-xl border border-slate-700 hover:border-amber-500 hover:shadow-[0_0_20px_rgba(245,158,11,0.2)] transition group">
                            <div class="text-4xl mb-3 group-hover:scale-110 transition">🧹</div>
                            <h3 class="font-bold text-lg text-white">Бытовая помощь</h3>
                            <p class="text-sm text-slate-400 mt-1">Уборка, стирка, готовка, уход за растениями. Легкий физический труд.</p>
                        </div>
                        <div @click="form.service_type = 'Уход'; nextStep()" class="cursor-pointer bg-slate-800 p-6 rounded-xl border border-slate-700 hover:border-amber-500 hover:shadow-[0_0_20px_rgba(245,158,11,0.2)] transition group">
                            <div class="text-4xl mb-3 group-hover:scale-110 transition">🧡</div>
                            <h3 class="font-bold text-lg text-white">Персональный уход</h3>
                            <p class="text-sm text-slate-400 mt-1">Гигиена, помощь в одевании, прием пищи. Требуется опытный помощник.</p>
                        </div>
                        <div @click="form.service_type = 'Сопровождение'; nextStep()" class="cursor-pointer bg-slate-800 p-6 rounded-xl border border-slate-700 hover:border-amber-500 hover:shadow-[0_0_20px_rgba(245,158,11,0.2)] transition group">
                            <div class="text-4xl mb-3 group-hover:scale-110 transition">🚗</div>
                            <h3 class="font-bold text-lg text-white">Сопровождение</h3>
                            <p class="text-sm text-slate-400 mt-1">Визиты к врачу, в банк, поездки по городу (на авто помощника или такси).</p>
                        </div>
                    </div>
                </div>

                {{-- STEP 2: BENEFICIARY (ClientProfile) --}}
                <div x-show="step === 2" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-4" style="display: none;">
                    <h2 class="text-3xl font-bold mb-2">Для кого помощь?</h2>
                    <p class="text-slate-400 mb-8">Эти данные помогут нам создать `ClientProfile`.</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Получатель</label>
                            <select x-model="form.relation" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 focus:ring-amber-500 focus:border-amber-500">
                                <option value="self">Я сам</option>
                                <option value="parent">Родитель (Отец/Мать)</option>
                                <option value="relative">Другой родственник</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Имя получателя</label>
                            <input type="text" x-model="form.name" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 focus:ring-amber-500 focus:border-amber-500" placeholder="Иван Иванович">
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Адрес (Нарвик)</label>
                        <input type="text" x-model="form.address" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 focus:ring-amber-500 focus:border-amber-500" placeholder="Улица, дом, квартира">
                    </div>

                    <div class="bg-slate-800/50 p-6 rounded-xl border border-slate-700">
                        <h4 class="text-amber-500 font-bold mb-4 text-sm">Важные детали</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <label class="flex items-center space-x-3 cursor-pointer">
                                <input type="checkbox" x-model="form.has_pets" class="form-checkbox text-amber-500 bg-slate-900 border-slate-600 rounded">
                                <span class="text-sm">Есть домашние животные</span>
                            </label>
                            <label class="flex items-center space-x-3 cursor-pointer">
                                <input type="checkbox" x-model="form.mobility_issues" class="form-checkbox text-amber-500 bg-slate-900 border-slate-600 rounded">
                                <span class="text-sm">Ограниченная мобильность</span>
                            </label>
                             <label class="flex items-center space-x-3 cursor-pointer">
                                <input type="checkbox" x-model="form.memory_issues" class="form-checkbox text-amber-500 bg-slate-900 border-slate-600 rounded">
                                <span class="text-sm">Проблемы с памятью</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-between mt-8">
                        <button @click="step--" class="text-slate-400 hover:text-white px-6">Назад</button>
                        <button @click="nextStep()" class="bg-amber-500 text-black font-bold py-3 px-8 rounded-xl hover:bg-amber-400">Далее</button>
                    </div>
                </div>

                {{-- STEP 3: SCHEDULE (CarePlan) --}}
                <div x-show="step === 3" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-4" style="display: none;">
                    <h2 class="text-3xl font-bold mb-2">Настройка Плана</h2>
                    <p class="text-slate-400 mb-8">Создаем `CarePlan`. Выберите удобный график.</p>

                    <div class="mb-8">
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-4">Частота визитов</label>
                        <div class="grid grid-cols-3 gap-4">
                            <button @click="form.frequency = 'once'" :class="form.frequency === 'once' ? 'bg-amber-500 text-black border-amber-500' : 'bg-slate-800 text-slate-400 border-slate-700'" class="border p-4 rounded-xl font-bold transition">Разово</button>
                            <button @click="form.frequency = 'weekly'" :class="form.frequency === 'weekly' ? 'bg-amber-500 text-black border-amber-500' : 'bg-slate-800 text-slate-400 border-slate-700'" class="border p-4 rounded-xl font-bold transition">Еженедельно</button>
                            <button @click="form.frequency = 'daily'" :class="form.frequency === 'daily' ? 'bg-amber-500 text-black border-amber-500' : 'bg-slate-800 text-slate-400 border-slate-700'" class="border p-4 rounded-xl font-bold transition">Ежедневно</button>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Длительность (часов)</label>
                            <input type="number" x-model="form.duration" min="1" max="12" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 focus:ring-amber-500 focus:border-amber-500">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Предпочтительное время</label>
                            <select x-model="form.time_slot" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 focus:ring-amber-500 focus:border-amber-500">
                                <option value="morning">Утро (08:00 - 12:00)</option>
                                <option value="afternoon">День (12:00 - 17:00)</option>
                                <option value="evening">Вечер (17:00 - 21:00)</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-between mt-8">
                        <button @click="step--" class="text-slate-400 hover:text-white px-6">Назад</button>
                        <button @click="nextStep()" class="bg-amber-500 text-black font-bold py-3 px-8 rounded-xl hover:bg-amber-400">Далее</button>
                    </div>
                </div>

                {{-- STEP 4: HELPER PREFERENCES (Matching) --}}
                <div x-show="step === 4" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-4" style="display: none;">
                    <h2 class="text-3xl font-bold mb-2">Ваша Пчелка</h2>
                    <p class="text-slate-400 mb-8">Критерии для алгоритма подбора `MatchingService`.</p>

                    <div class="space-y-6">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Язык общения</label>
                            <div class="flex flex-wrap gap-3">
                                <label class="cursor-pointer">
                                    <input type="radio" name="lang" value="no" class="peer sr-only" x-model="form.language">
                                    <div class="px-4 py-2 rounded-full border border-slate-700 text-slate-400 peer-checked:bg-amber-500 peer-checked:text-black peer-checked:border-amber-500 transition">Norsk</div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="lang" value="en" class="peer sr-only" x-model="form.language">
                                    <div class="px-4 py-2 rounded-full border border-slate-700 text-slate-400 peer-checked:bg-amber-500 peer-checked:text-black peer-checked:border-amber-500 transition">English</div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="lang" value="ru" class="peer sr-only" x-model="form.language">
                                    <div class="px-4 py-2 rounded-full border border-slate-700 text-slate-400 peer-checked:bg-amber-500 peer-checked:text-black peer-checked:border-amber-500 transition">Русский/Українська</div>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Предпочтение по полу</label>
                            <div class="flex flex-wrap gap-3">
                                <label class="cursor-pointer">
                                    <input type="radio" name="gender" value="any" class="peer sr-only" x-model="form.helper_gender">
                                    <div class="px-4 py-2 rounded-full border border-slate-700 text-slate-400 peer-checked:bg-amber-500 peer-checked:text-black peer-checked:border-amber-500 transition">Не важно</div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="gender" value="female" class="peer sr-only" x-model="form.helper_gender">
                                    <div class="px-4 py-2 rounded-full border border-slate-700 text-slate-400 peer-checked:bg-amber-500 peer-checked:text-black peer-checked:border-amber-500 transition">Женщина</div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" name="gender" value="male" class="peer sr-only" x-model="form.helper_gender">
                                    <div class="px-4 py-2 rounded-full border border-slate-700 text-slate-400 peer-checked:bg-amber-500 peer-checked:text-black peer-checked:border-amber-500 transition">Мужчина</div>
                                </label>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Комментарий к заказу</label>
                            <textarea x-model="form.notes" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 focus:ring-amber-500 focus:border-amber-500 h-24" placeholder="Например: Мама любит вязать, нужен кто-то, кто поддержит беседу."></textarea>
                        </div>
                    </div>

                    <div class="flex justify-between mt-8">
                        <button @click="step--" class="text-slate-400 hover:text-white px-6">Назад</button>
                        <button @click="submitOrder()" class="bg-green-600 text-white font-bold py-3 px-8 rounded-xl hover:bg-green-500 shadow-lg shadow-green-500/20">Отправить заявку</button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- 3. BOTTOM SECTION: ПУТЬ ЗАБОТЫ (Timeline) --}}
    <div class="container mx-auto px-4 pb-24">
        <h2 class="text-3xl font-bold text-center mb-16">Что произойдет дальше?</h2>
        
        <div class="relative max-w-4xl mx-auto">
            <div class="absolute left-1/2 top-0 bottom-0 w-0.5 bg-slate-800 -translate-x-1/2 hidden md:block"></div>
            
            <div class="flex flex-col md:flex-row items-center mb-12 relative z-10">
                <div class="w-full md:w-1/2 pr-0 md:pr-12 text-center md:text-right mb-4 md:mb-0">
                    <h4 class="text-xl font-bold text-amber-500">1. Анализ профиля</h4>
                    <p class="text-slate-400 text-sm mt-2">Координатор проверяет `CarePlan` и медицинские требования.</p>
                </div>
                <div class="w-12 h-12 bg-slate-900 border-2 border-amber-500 rounded-full flex items-center justify-center text-amber-500 font-bold shadow-[0_0_15px_rgba(245,158,11,0.5)]">1</div>
                <div class="w-full md:w-1/2 pl-0 md:pl-12 hidden md:block"></div>
            </div>

            <div class="flex flex-col md:flex-row items-center mb-12 relative z-10">
                <div class="w-full md:w-1/2 pr-0 md:pr-12 hidden md:block"></div>
                <div class="w-12 h-12 bg-slate-900 border-2 border-slate-600 rounded-full flex items-center justify-center text-slate-400 font-bold">2</div>
                <div class="w-full md:w-1/2 pl-0 md:pl-12 text-center md:text-left mt-4 md:mt-0">
                    <h4 class="text-xl font-bold text-white">2. Подбор Пчелки</h4>
                    <p class="text-slate-400 text-sm mt-2">Алгоритм находит помощника с рейтингом 4.8+ и нужным языком.</p>
                </div>
            </div>

            <div class="flex flex-col md:flex-row items-center relative z-10">
                <div class="w-full md:w-1/2 pr-0 md:pr-12 text-center md:text-right mb-4 md:mb-0">
                    <h4 class="text-xl font-bold text-white">3. Знакомство</h4>
                    <p class="text-slate-400 text-sm mt-2">Бесплатный первый визит (15 мин) для знакомства и утверждения плана.</p>
                </div>
                <div class="w-12 h-12 bg-slate-900 border-2 border-slate-600 rounded-full flex items-center justify-center text-slate-400 font-bold">3</div>
                <div class="w-full md:w-1/2 pl-0 md:pl-12 hidden md:block"></div>
            </div>
        </div>
    </div>
    
    {{-- FOOTER INCLUDE PLACEHOLDER --}}
    <footer class="bg-slate-900 py-10 border-t border-slate-800 text-center text-slate-500 text-sm">
        <div class="container mx-auto">
            <p>&copy; 2025 GLF BiKube. Social Care Module.</p>
        </div>
    </footer>

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
</style>

<script>
function socialPage() {
    return {
        step: 1,
        steps: ['Тип помощи', 'Получатель', 'График', 'Пчелка'],
        
        // Данные формы, соответствующие моделям БД
        form: {
            service_type: '',
            
            // ClientProfile
            relation: 'self',
            name: '',
            address: '',
            has_pets: false,
            mobility_issues: false,
            memory_issues: false,
            
            // CarePlan
            frequency: 'weekly',
            duration: 2,
            time_slot: 'morning',
            
            // Matching / HelperProfile
            language: 'no',
            helper_gender: 'any',
            notes: ''
        },

        initPage() {
            console.log('Care Wizard Loaded');
        },

        nextStep() {
            if (this.step < 4) {
                this.step++;
                this.scrollToWizard();
            }
        },

        scrollToWizard() {
            document.getElementById('care-wizard').scrollIntoView({ behavior: 'smooth' });
        },

        async submitOrder() {
            // Validation
            if (!this.form.service_type) {
                alert('Пожалуйста, выберите тип услуги');
                return;
            }
            
            if (!this.form.name || !this.form.address) {
                alert('Пожалуйста, заполните имя получателя и адрес');
                return;
            }
            
            if (!this.form.frequency || !this.form.duration) {
                alert('Пожалуйста, укажите частоту и длительность визитов');
                return;
            }
            
            // Prepare customer data (if not provided, use beneficiary data)
            const customerData = {
                name: this.form.name,
                email: prompt('Введите ваш email для связи:') || 'customer@example.com',
                phone: prompt('Введите ваш телефон:') || '+47 000 00 000',
            };
            
            if (!customerData.email || !customerData.phone) {
                alert('Пожалуйста, укажите контактные данные');
                return;
            }
            
            try {
                const response = await fetch('/api/v1/care/orders', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        ...this.form,
                        customer: customerData,
                    }),
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert(`Заявка успешно создана! Номер заказа: ${data.data.order_number}\n${data.message}`);
                    // Reset form or redirect
                    window.location.href = '/';
                } else {
                    const errorMsg = data.errors ? Object.values(data.errors).flat().join(', ') : (data.message || 'Неизвестная ошибка');
                    alert('Ошибка при создании заявки: ' + errorMsg);
                    console.error('Order creation error:', data);
                }
            } catch (error) {
                console.error('Network error:', error);
                alert('Ошибка сети. Пожалуйста, проверьте подключение и попробуйте еще раз.');
            }
        }
    }
}
</script>
@endsection
