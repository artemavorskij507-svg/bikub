@extends('layouts.app')

@section('content')
<div x-data="towPage()" x-init="initPage()" class="min-h-screen bg-slate-900 text-white font-sans relative overflow-x-hidden">

    {{-- ФОНОВАЯ КАРТА (Заглушка или Google Maps API) --}}
    <div class="fixed inset-0 z-0 opacity-30 grayscale pointer-events-none">
        <img src="https://images.unsplash.com/photo-1524661135-423995f22d0b?auto=format&fit=crop&w=2074&q=80" class="w-full h-full object-cover">
    </div>

    {{-- HEADER --}}
    <div class="relative z-10 container mx-auto px-4 pt-6 pb-4 flex justify-between items-center">
        <div class="flex items-center space-x-2">
            <div class="bg-orange-500 text-black font-bold px-3 py-1 rounded uppercase tracking-widest text-xs">Alle Tow</div>
            <span class="text-orange-500 font-bold animate-pulse">● 24/7 Online</span>
        </div>
        <div class="text-sm text-gray-400">Нарвик и окрестности</div>
    </div>

    {{-- MAIN INTERFACE --}}
    <div class="relative z-10 container mx-auto px-4 pb-20 pt-4">
        <div class="max-w-3xl mx-auto">

            {{-- ШАГ 1: ЧТО СЛУЧИЛОСЬ? (Главный экран) --}}
            <div x-show="step === 1" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform scale-95">
                <h1 class="text-4xl md:text-5xl font-bold mb-2 text-center">Нужна помощь на дороге?</h1>
                <p class="text-center text-gray-400 mb-8">Выберите тип проблемы, и мы направим ближайший экипаж.</p>

                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-8">
                    <button @click="selectService('towing')" class="bg-slate-800/80 backdrop-blur border border-slate-700 hover:border-orange-500 hover:bg-slate-700 p-6 rounded-2xl transition group flex flex-col items-center text-center">
                        <div class="w-16 h-16 bg-orange-500/10 rounded-full flex items-center justify-center mb-4 group-hover:bg-orange-500 text-orange-500 group-hover:text-black transition">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" /></svg>
                        </div>
                        <h3 class="font-bold text-lg">Эвакуатор</h3>
                        <p class="text-xs text-gray-400 mt-1">ДТП или поломка</p>
                    </button>

                    <button @click="selectService('battery')" class="bg-slate-800/80 backdrop-blur border border-slate-700 hover:border-yellow-500 hover:bg-slate-700 p-6 rounded-2xl transition group flex flex-col items-center text-center">
                        <div class="w-16 h-16 bg-yellow-500/10 rounded-full flex items-center justify-center mb-4 group-hover:bg-yellow-500 text-yellow-500 group-hover:text-black transition">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" /></svg>
                        </div>
                        <h3 class="font-bold text-lg">Сел аккумулятор</h3>
                        <p class="text-xs text-gray-400 mt-1">Запуск бустером</p>
                    </button>

                    <button @click="selectService('tyre')" class="bg-slate-800/80 backdrop-blur border border-slate-700 hover:border-blue-500 hover:bg-slate-700 p-6 rounded-2xl transition group flex flex-col items-center text-center">
                        <div class="w-16 h-16 bg-blue-500/10 rounded-full flex items-center justify-center mb-4 group-hover:bg-blue-500 text-blue-500 group-hover:text-black transition">
                            <div class="text-2xl font-bold">⭕️</div>
                        </div>
                        <h3 class="font-bold text-lg">Пробито колесо</h3>
                        <p class="text-xs text-gray-400 mt-1">Замена на месте</p>
                    </button>

                    <button @click="selectService('lockout')" class="bg-slate-800/80 backdrop-blur border border-slate-700 hover:border-purple-500 hover:bg-slate-700 p-6 rounded-2xl transition group flex flex-col items-center text-center">
                        <div class="w-16 h-16 bg-purple-500/10 rounded-full flex items-center justify-center mb-4 group-hover:bg-purple-500 text-purple-500 group-hover:text-black transition">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
                        </div>
                        <h3 class="font-bold text-lg">Вскрытие</h3>
                        <p class="text-xs text-gray-400 mt-1">Ключи внутри</p>
                    </button>
                    
                    <button @click="selectService('fuel')" class="bg-slate-800/80 backdrop-blur border border-slate-700 hover:border-green-500 hover:bg-slate-700 p-6 rounded-2xl transition group flex flex-col items-center text-center">
                         <div class="w-16 h-16 bg-green-500/10 rounded-full flex items-center justify-center mb-4 group-hover:bg-green-500 text-green-500 group-hover:text-black transition">
                            <div class="text-2xl font-bold">⛽️</div>
                        </div>
                        <h3 class="font-bold text-lg">Топливо</h3>
                        <p class="text-xs text-gray-400 mt-1">Бензин/Дизель</p>
                    </button>
                    
                    <button @click="selectService('winch')" class="bg-slate-800/80 backdrop-blur border border-slate-700 hover:border-red-500 hover:bg-slate-700 p-6 rounded-2xl transition group flex flex-col items-center text-center">
                        <div class="w-16 h-16 bg-red-500/10 rounded-full flex items-center justify-center mb-4 group-hover:bg-red-500 text-red-500 group-hover:text-black transition">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                        </div>
                        <h3 class="font-bold text-lg">SOS / Застрял</h3>
                        <p class="text-xs text-gray-400 mt-1">Вытянуть из кювета</p>
                    </button>
                </div>
                
                <div class="bg-slate-800/50 p-4 rounded-xl text-center border border-slate-700">
                    <p class="text-sm text-gray-400">Если есть пострадавшие, звоните <span class="text-red-500 font-bold text-lg">113</span> или <span class="text-red-500 font-bold text-lg">112</span></p>
                </div>
            </div>

            {{-- ШАГ 2: ЛОКАЦИЯ (Геолокация) --}}
            <div x-show="step === 2" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-x-10" style="display: none;">
                <button @click="step = 1" class="mb-4 text-gray-400 hover:text-white flex items-center">← Назад</button>
                
                <div class="bg-slate-800 rounded-3xl p-8 border border-slate-700 shadow-2xl text-center">
                    <h2 class="text-3xl font-bold mb-6">Где вы находитесь?</h2>
                    
                    <div class="relative h-48 bg-slate-900 rounded-xl mb-6 flex items-center justify-center overflow-hidden border border-slate-600">
                        <div x-show="locating" class="absolute inset-0 flex items-center justify-center">
                             <div class="w-24 h-24 bg-orange-500 rounded-full opacity-20 animate-ping"></div>
                             <div class="w-12 h-12 bg-orange-500 rounded-full opacity-40 animate-pulse absolute"></div>
                        </div>
                        
                        <div x-show="!locating && locationFound" class="text-center">
                             <div class="text-4xl mb-2">📍</div>
                             <p class="font-bold text-white" x-text="address"></p>
                             <p class="text-xs text-gray-500" x-text="coordinates"></p>
                        </div>
                        
                        <button x-show="!locating && !locationFound" @click="findMe()" class="bg-orange-500 hover:bg-orange-400 text-black font-bold py-3 px-6 rounded-full shadow-lg transform hover:scale-105 transition flex items-center mx-auto">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>
                            Определить местоположение
                        </button>
                    </div>
                    
                    <div x-show="locationFound" class="animate-fade-in-up">
                        <label class="block text-left text-sm font-bold text-gray-400 mb-2">Детали (ориентир, цвет машины, номер)</label>
                        <textarea x-model="details" class="w-full bg-slate-900 border border-slate-600 rounded-xl p-4 text-white focus:border-orange-500 focus:ring-orange-500 mb-6" rows="2" placeholder="Например: Серый Volvo, номер AB12345, стою у отбойника"></textarea>
                        
                        <div x-show="service === 'towing'" class="mb-6 text-left">
                            <label class="block text-sm font-bold text-gray-400 mb-2">Куда везти?</label>
                            <select x-model="destination" class="w-full bg-slate-900 border border-slate-600 rounded-xl p-3 text-white">
                                <option value="nearest">Ближайший сервис (Рекомендуем)</option>
                                <option value="home">Домой (в пределах города)</option>
                                <option value="custom">Другой адрес (+ оплата за км)</option>
                            </select>
                        </div>

                        <button @click="calculateEstimate()" class="w-full bg-orange-500 hover:bg-orange-400 text-black font-bold py-4 rounded-xl text-lg shadow-lg shadow-orange-500/20">
                            Далее
                        </button>
                    </div>
                </div>
            </div>

            {{-- ШАГ 3: РАСЧЕТ И UPSELL (Smart Bundle) --}}
            <div x-show="step === 3" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-x-10" style="display: none;">
                <button @click="step = 2" class="mb-4 text-gray-400 hover:text-white flex items-center">← Назад</button>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-slate-800 rounded-3xl p-8 border border-slate-700 h-fit">
                        <h2 class="text-2xl font-bold mb-6 text-orange-500">Смета помощи</h2>
                        
                        <div class="space-y-4 text-sm mb-8">
                            <div class="flex justify-between">
                                <span class="text-gray-400">Выезд экипажа</span>
                                <span>1 290 kr</span>
                            </div>
                            <div x-show="service === 'towing'" class="flex justify-between">
                                <span class="text-gray-400">Погрузка/Разгрузка</span>
                                <span>500 kr</span>
                            </div>
                            <div x-show="service !== 'towing'" class="flex justify-between">
                                <span class="text-gray-400">Работа мастера</span>
                                <span>450 kr</span>
                            </div>
                            <div class="pt-4 border-t border-slate-600 flex justify-between items-end">
                                <span class="text-lg font-bold">Итого:</span>
                                <span class="text-3xl font-bold text-orange-500" x-text="totalPrice + ' kr'"></span>
                            </div>
                        </div>
                        
                        <button class="w-full bg-green-600 hover:bg-green-500 text-white font-bold py-4 rounded-xl text-lg shadow-lg flex justify-center items-center">
                            <span class="animate-pulse mr-2">●</span> Вызвать экипаж
                        </button>
                        <p class="text-center text-xs text-gray-500 mt-3">ETA: ~25 минут</p>
                    </div>

                    <div class="space-y-4">
                        <div x-show="service === 'towing'" class="bg-slate-800 border border-indigo-500/30 rounded-2xl p-6 relative overflow-hidden">
                            <div class="absolute top-0 right-0 bg-indigo-600 text-xs px-2 py-1 rounded-bl-lg text-white font-bold">Рекомендуем</div>
                            <div class="flex items-start mb-4">
                                <div class="bg-indigo-900/50 p-3 rounded-lg mr-4 text-2xl">🚖</div>
                                <div>
                                    <h4 class="font-bold text-lg">Вас нужно подвезти?</h4>
                                    <p class="text-gray-400 text-sm">Ваша машина поедет на сервисе. Закажите Alle Shuttle до дома прямо сейчас.</p>
                                </div>
                            </div>
                            <label class="flex items-center p-3 bg-slate-900 rounded-lg border border-slate-700 cursor-pointer hover:border-indigo-500 transition">
                                <input type="checkbox" x-model="upsell.taxi" class="w-5 h-5 rounded text-indigo-600 bg-slate-800 border-gray-600 focus:ring-indigo-500">
                                <span class="ml-3 text-sm">Добавить такси (+250 kr)</span>
                            </label>
                        </div>

                        <div x-show="service === 'towing'" class="bg-slate-800 border border-blue-500/30 rounded-2xl p-6">
                            <div class="flex items-start mb-4">
                                <div class="bg-blue-900/50 p-3 rounded-lg mr-4 text-2xl">🚙</div>
                                <div>
                                    <h4 class="font-bold text-lg">Нужна машина на завтра?</h4>
                                    <p class="text-gray-400 text-sm">Арендуйте авто из нашего парка, пока ваша в ремонте.</p>
                                </div>
                            </div>
                            <a href="/category/rent" target="_blank" class="block text-center w-full border border-blue-500 text-blue-400 py-2 rounded-lg hover:bg-blue-500 hover:text-white transition text-sm">
                                Посмотреть авто в аренду
                            </a>
                        </div>
                        
                        <div x-show="service !== 'towing'" class="bg-slate-800 border border-gray-700 rounded-2xl p-6">
                            <div class="flex items-start mb-4">
                                <div class="bg-gray-700 p-3 rounded-lg mr-4 text-2xl">🔧</div>
                                <div>
                                    <h4 class="font-bold text-lg">Записаться на диагностику?</h4>
                                    <p class="text-gray-400 text-sm">Чтобы проблема не повторилась, рекомендуем заехать в партнерский сервис.</p>
                                </div>
                            </div>
                             <label class="flex items-center p-3 bg-slate-900 rounded-lg border border-slate-700 cursor-pointer hover:border-gray-500 transition">
                                <input type="checkbox" class="w-5 h-5 rounded text-gray-600 bg-slate-800 border-gray-600">
                                <span class="ml-3 text-sm">Отправить заявку партнерам</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>

<script>
function towPage() {
    return {
        step: 1,
        service: null,
        locating: false,
        locationFound: false,
        address: '',
        coordinates: '',
        details: '',
        destination: 'nearest',
        
        upsell: {
            taxi: false
        },

        initPage() {
            console.log('Tow Service Ready');
        },

        selectService(type) {
            this.service = type;
            this.step = 2;
        },

        findMe() {
            this.locating = true;
            // Эмуляция API геолокации
            if ("geolocation" in navigator) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        setTimeout(() => {
                            this.address = 'E6, 8514 Narvik (Примерно)';
                            this.coordinates = position.coords.latitude.toFixed(4) + ', ' + position.coords.longitude.toFixed(4);
                            this.locating = false;
                            this.locationFound = true;
                        }, 1500);
                    },
                    (error) => {
                        this.locating = false;
                        alert('Не удалось определить геопозицию. Введите адрес вручную.');
                        this.locationFound = true;
                        this.address = 'Адрес не определен';
                    }
                );
            } else {
                this.locating = false;
                this.locationFound = true;
            }
        },
        
        calculateEstimate() {
            this.step = 3;
        },

        get totalPrice() {
            let base = 1290; // База
            let extra = 0;
            
            if (this.service === 'towing') {
                extra += 500; // Погрузка
                if (this.destination === 'custom') extra += 400;
            } else {
                extra += 450; // Работа на месте
            }
            
            if (this.upsell.taxi) {
                extra += 250;
            }
            
            return base + extra;
        }
    }
}
</script>
@endsection

