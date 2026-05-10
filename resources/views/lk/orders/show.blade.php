@extends('lk.layout')

@section('title', 'Заказ #' . ($order->order_number ?? $order->id))

@section('content')
<div class="space-y-10" data-scroll-container>
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-50 border border-blue-100 text-xs font-bold uppercase tracking-widest text-blue-600 mb-3">
                <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                Детали заказа
            </div>
            <h1 class="text-4xl font-black text-slate-900 tracking-tight">Заказ #{{ $order->order_number ?? $order->id }}</h1>
            <p class="text-slate-500 font-medium mt-2">Полная информация и управление заказом</p>
        </div>
        <a href="{{ route('lk.orders.index') }}" class="group inline-flex items-center gap-2 px-6 py-3 bg-white border border-slate-200 rounded-2xl font-bold text-slate-600 hover:bg-slate-50 hover:border-slate-300 hover:text-slate-800 transition-all shadow-sm">
            <svg class="w-5 h-5 transition-transform group-hover:-translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            Назад к списку
        </a>
    </div>

    @if($order)
        @php
            $isRoadside = $order->isRoadside();
            $roadsideDetail = $order->roadsideDetails;
            $roadsideEmergency = $order->roadsideEmergency;
        @endphp

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Main Info --}}
            <div class="lg:col-span-2 space-y-8">
                {{-- Roadside Panel --}}
                @if($isRoadside)
                    <div class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-[2.5rem] p-8 border border-amber-100 shadow-lg relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-64 h-64 bg-white/40 rounded-full -mr-20 -mt-20 blur-3xl pointer-events-none"></div>
                        
                        <div class="flex items-center gap-4 mb-8 relative z-10">
                            <div class="w-14 h-14 bg-amber-500 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-amber-500/30">
                                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                            </div>
                            <div>
                                <h2 class="text-2xl font-black text-slate-900 tracking-tight">Roadside Assist</h2>
                                <p class="text-amber-700 font-bold opacity-80">Экстренная помощь на дороге</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 relative z-10">
                            {{-- Vehicle --}}
                            @if($roadsideDetail || ($roadsideEmergency && ($roadsideEmergency->metadata['vehicle_plate'] ?? $roadsideEmergency->metadata['vehicle_make'] ?? null)))
                                <div class="bg-white/60 backdrop-blur-md rounded-2xl p-5 border border-white/50">
                                    <h3 class="text-xs font-bold text-amber-800 uppercase tracking-wider mb-3">Автомобиль</h3>
                                    <div class="space-y-2">
                                        @if($roadsideDetail)
                                            @if($roadsideDetail->vehicle_make || $roadsideDetail->vehicle_model)
                                                <div class="font-black text-slate-900 text-lg">{{ $roadsideDetail->vehicle_make ?? '' }} {{ $roadsideDetail->vehicle_model ?? '' }}</div>
                                            @endif
                                            @if($roadsideDetail->vehicle_plate)
                                                <div class="inline-block px-3 py-1 bg-slate-900 text-white rounded-lg font-mono text-sm font-bold tracking-wider">{{ $roadsideDetail->vehicle_plate }}</div>
                                            @endif
                                            @if($roadsideDetail->vehicle_color)
                                                <div class="text-sm font-bold text-slate-500">Цвет: {{ $roadsideDetail->vehicle_color }}</div>
                                            @endif
                                        @elseif($roadsideEmergency)
                                            <div class="font-black text-slate-900 text-lg">
                                                {{ $roadsideEmergency->metadata['vehicle_make'] ?? '' }} {{ $roadsideEmergency->metadata['vehicle_model'] ?? '' }}
                                            </div>
                                            @if($roadsideEmergency->metadata['vehicle_plate'] ?? null)
                                                <div class="inline-block px-3 py-1 bg-slate-900 text-white rounded-lg font-mono text-sm font-bold tracking-wider">{{ $roadsideEmergency->metadata['vehicle_plate'] }}</div>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            @endif

                            {{-- Task Type --}}
                            <div class="bg-white/60 backdrop-blur-md rounded-2xl p-5 border border-white/50">
                                <h3 class="text-xs font-bold text-amber-800 uppercase tracking-wider mb-3">Тип задачи</h3>
                                <div class="font-black text-slate-900 text-lg">
                                    @if($roadsideEmergency)
                                        @php
                                            $types = [
                                                'jump_start' => '🔋 Прикурить',
                                                'fuel' => '⛽ Топливо',
                                                'flat_tire' => '🛞 Заменить колесо',
                                                'locked_keys' => '🔑 Открыть авто',
                                                'engine_no_start' => '🚗 Не заводится',
                                                'tow_needed' => '🚛 Эвакуация',
                                                'accident' => '⚠️ ДТП',
                                            ];
                                        @endphp
                                        {{ $types[$roadsideEmergency->incident_type] ?? $roadsideEmergency->incident_type }}
                                    @elseif($roadsideDetail)
                                        @php
                                            $preset = \App\Models\RoadsidePreset::where('code', $roadsideDetail->subtype)->first();
                                        @endphp
                                        {{ $preset ? $preset->label : ($roadsideDetail->subtype ?? 'Помощь на дороге') }}
                                    @else
                                        Помощь на дороге
                                    @endif
                                </div>
                            </div>

                            {{-- Location --}}
                            <div class="md:col-span-2 bg-white/60 backdrop-blur-md rounded-2xl p-5 border border-white/50">
                                <h3 class="text-xs font-bold text-amber-800 uppercase tracking-wider mb-3">Локация</h3>
                                <div class="font-bold text-slate-900 text-base leading-snug">
                                    @if($roadsideDetail && $roadsideDetail->incident_address)
                                        {{ $roadsideDetail->incident_address }}
                                    @elseif($roadsideEmergency)
                                        {{ $roadsideEmergency->metadata['location_text'] ?? 'Адрес не указан' }}
                                    @else
                                        Адрес не указан
                                    @endif
                                </div>
                                @if($roadsideEmergency && $roadsideEmergency->lat && $roadsideEmergency->lng)
                                    <div class="mt-3 flex items-center gap-3">
                                        <a href="https://www.google.com/maps?q={{ $roadsideEmergency->lat }},{{ $roadsideEmergency->lng }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 text-white rounded-xl text-xs font-bold hover:bg-black transition-colors">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                            Открыть на карте
                                        </a>
                                        <div class="text-xs font-mono font-medium text-slate-500">{{ number_format($roadsideEmergency->lat, 6) }}, {{ number_format($roadsideEmergency->lng, 6) }}</div>
                                    </div>
                                @endif
                                @if($roadsideEmergency && $roadsideEmergency->incident_description)
                                    <div class="mt-3 p-3 bg-amber-100/50 rounded-xl text-sm font-medium text-amber-900 italic border border-amber-100">
                                        "{{ $roadsideEmergency->incident_description }}"
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Order Info --}}
                <div class="bg-white rounded-[2.5rem] p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100">
                    <div class="flex items-center justify-between mb-8">
                        <h2 class="text-2xl font-black text-slate-900 tracking-tight">Информация</h2>
                        @php
                            $statusConfig = [
                                'pending' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'label' => 'Ожидает'],
                                'confirmed' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'label' => 'Подтвержден'],
                                'assigned' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-800', 'label' => 'Назначен'],
                                'in_progress' => ['bg' => 'bg-sky-100', 'text' => 'text-sky-800', 'label' => 'В работе'],
                                'completed' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-800', 'label' => 'Завершен'],
                                'delivered' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-800', 'label' => 'Доставлен'],
                                'cancelled' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'label' => 'Отменен'],
                            ];
                            $st = $statusConfig[$order->status] ?? ['bg' => 'bg-slate-100', 'text' => 'text-slate-800', 'label' => $order->status];
                        @endphp
                        <span class="px-4 py-2 rounded-xl text-sm font-black uppercase tracking-wider {{ $st['bg'] }} {{ $st['text'] }}">
                            {{ $st['label'] }}
                        </span>
                    </div>

                    <div class="space-y-8">
                        {{-- Клиент --}}
                        @if($order->user)
                            <div class="p-6 bg-slate-50 rounded-3xl border border-slate-100">
                                <div class="flex items-center gap-4 mb-4">
                                    <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center text-slate-400 shadow-sm border border-slate-100">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                    </div>
                                    <div>
                                        <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Клиент</div>
                                        <div class="text-lg font-black text-slate-900">{{ $order->user->name ?? 'Не указан' }}</div>
                                    </div>
                                </div>
                                @if($order->user->phone)
                                    <a href="tel:{{ $order->user->phone }}" class="flex items-center justify-between p-4 bg-white rounded-2xl border border-slate-200 hover:border-green-300 hover:shadow-md transition-all group">
                                        <span class="font-bold text-slate-700 group-hover:text-green-700">{{ $order->user->phone }}</span>
                                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center text-green-600 group-hover:scale-110 transition-transform">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                        </div>
                                    </a>
                                @endif
                            </div>
                        @endif

                        {{-- Адрес --}}
                        @if($order->address || $order->location)
                            <div class="relative pl-8 border-l-2 border-slate-100 space-y-6">
                                <div class="absolute left-[-5px] top-0 w-3 h-3 bg-slate-900 rounded-full ring-4 ring-white"></div>
                                <div>
                                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Адрес доставки</h3>
                                    <p class="text-lg font-bold text-slate-900 leading-snug">
                                        {{ $order->address->formatted_address ?? $order->address->street_address ?? ($order->location['address'] ?? 'Адрес не указан') }}
                                    </p>
                                    @if($order->address && $order->address->city)
                                        <p class="text-sm font-medium text-slate-500 mt-1">{{ $order->address->city }}, {{ $order->address->postal_code ?? '' }}</p>
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- Детали заказа --}}
                        <div class="grid grid-cols-2 gap-6 pt-6 border-t border-slate-100">
                            <div>
                                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Создан</div>
                                <div class="font-bold text-slate-900">{{ $order->created_at ? $order->created_at->format('d.m.Y H:i') : '-' }}</div>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Завершен</div>
                                <div class="font-bold text-slate-900">{{ $order->completed_at ? $order->completed_at->format('d.m.Y H:i') : '-' }}</div>
                            </div>
                            @if($order->priority)
                                <div>
                                    <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Приоритет</div>
                                    @php
                                        $prioLabels = ['low'=>'Низкий', 'normal'=>'Обычный', 'high'=>'Высокий', 'urgent'=>'Срочный'];
                                    @endphp
                                    <div class="font-bold text-slate-900">{{ $prioLabels[$order->priority] ?? $order->priority }}</div>
                                </div>
                            @endif
                        </div>

                        @if($order->notes)
                            <div class="p-6 bg-yellow-50 rounded-3xl border border-yellow-100">
                                <div class="flex items-center gap-2 mb-2 text-yellow-800 font-bold text-sm uppercase tracking-wider">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" /></svg>
                                    Заметки
                                </div>
                                <p class="text-slate-700 font-medium whitespace-pre-wrap">{{ $order->notes }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Payment --}}
                <div class="bg-white rounded-[2.5rem] p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100">
                    <h2 class="text-xl font-black text-slate-900 mb-6 tracking-tight">Финансы</h2>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center p-4 bg-slate-50 rounded-2xl border border-slate-100">
                            <span class="text-sm font-bold text-slate-500">Сумма заказа</span>
                            <span class="text-xl font-black text-slate-900">{{ number_format($order->total_amount ?? 0, 0, ',', ' ') }} <span class="text-sm text-slate-400">kr</span></span>
                        </div>
                        
                        <div class="flex justify-between items-center p-4 bg-emerald-50 rounded-2xl border border-emerald-100">
                            <span class="text-sm font-bold text-emerald-700">Ваш доход</span>
                            <span class="text-xl font-black text-emerald-600">
                                @php
                                    $payout = $order->tasks->sum('payout_amount') ?? 0;
                                    if ($payout === 0 && isset($order->metadata['executor_payout'])) {
                                        $payout = $order->metadata['executor_payout'];
                                    }
                                @endphp
                                +{{ number_format($payout, 0, ',', ' ') }} <span class="text-sm opacity-70">kr</span>
                            </span>
                        </div>

                        <div class="flex items-center gap-3 pt-2">
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Статус оплаты:</span>
                            @php
                                $payColors = ['pending' => 'bg-yellow-100 text-yellow-800', 'paid' => 'bg-green-100 text-green-800', 'failed' => 'bg-red-100 text-red-800', 'refunded' => 'bg-slate-100 text-slate-800'];
                                $payLabels = ['pending' => 'Ожидает', 'paid' => 'Оплачено', 'failed' => 'Ошибка', 'refunded' => 'Возврат'];
                            @endphp
                            <span class="px-3 py-1 rounded-lg text-xs font-black uppercase tracking-wider {{ $payColors[$order->payment_status] ?? 'bg-slate-100 text-slate-800' }}">
                                {{ $payLabels[$order->payment_status] ?? $order->payment_status }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Actions & Timeline --}}
            <div class="space-y-8">
                {{-- Actions Panel --}}
                <div class="bg-white rounded-[2.5rem] p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 sticky top-6">
                    <h2 class="text-xl font-black text-slate-900 mb-6 tracking-tight">Действия</h2>
                    
                    {{-- Roadside Steps --}}
                    @if($isRoadside)
                        <div class="mb-8 space-y-0 relative">
                            <div class="absolute left-4 top-4 bottom-4 w-0.5 bg-slate-100"></div>
                            @php
                                $steps = [
                                    ['key' => 'accept', 'label' => 'Принять', 'status' => 'assigned', 'icon' => '✓'],
                                    ['key' => 'start_travel', 'label' => 'В пути', 'status' => 'in_progress', 'icon' => '🚗'],
                                    ['key' => 'arrived', 'label' => 'На месте', 'status' => 'in_progress', 'icon' => '📍'],
                                    ['key' => 'start_job', 'label' => 'Работа', 'status' => 'in_progress', 'icon' => '🔧'],
                                    ['key' => 'finish_job', 'label' => 'Финиш', 'status' => 'completed', 'icon' => '🏁'],
                                ];
                                // Logic to determine current step index (simplified)
                                $currentStepIndex = -1;
                                if ($order->status === 'completed') $currentStepIndex = 4;
                                elseif ($order->status === 'in_progress') {
                                    if(isset($order->metadata['job_started_at'])) $currentStepIndex = 3;
                                    elseif(isset($order->metadata['arrived_at'])) $currentStepIndex = 2;
                                    else $currentStepIndex = 1;
                                } elseif ($order->status === 'assigned') $currentStepIndex = 0;
                            @endphp
                            
                            @foreach($steps as $index => $step)
                                @php
                                    $isCompleted = $index <= $currentStepIndex;
                                    $isCurrent = $index === $currentStepIndex + 1;
                                    $isFuture = $index > $currentStepIndex + 1;
                                @endphp
                                <div class="relative flex items-center gap-4 py-2">
                                    <div class="relative z-10 w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold border-2 transition-all
                                        {{ $isCompleted ? 'bg-green-500 border-green-500 text-white' : ($isCurrent ? 'bg-white border-blue-500 text-blue-500 shadow-[0_0_0_4px_rgba(59,130,246,0.1)]' : 'bg-white border-slate-200 text-slate-300') }}">
                                        {{ $isCompleted ? '✓' : $index + 1 }}
                                    </div>
                                    <div class="font-bold text-sm {{ $isCompleted ? 'text-slate-900' : 'text-slate-400' }}">{{ $step['label'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div x-data="{
                        loading: false,
                        notice: '',
                        noticeType: 'info',
                        showFinishModal: false,
                        workSummary: '',
                        clientOk: false,
                        vehicleMoved: false,
                        recommendedService: false,
                        photosNote: '',
                        showRejectModal: false,
                        rejectReason: '',
                        showProblemModal: false,
                        problemReason: '',
                        async performAction(action, reason = null, extraData = null) {
                            if (this.loading) return;
                            this.notice = '';
                            this.loading = true;
                            try {
                                const response = await fetch('{{ route('lk.orders.action', $order) }}', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                                    body: JSON.stringify({ action, reason, ...(extraData || {}) }),
                                });
                                const data = await response.json();
                                if (data.success) {
                                    this.noticeType = 'success';
                                    this.notice = data.message || 'Action applied successfully.';
                                    window.location.reload();
                                } else {
                                    this.noticeType = 'error';
                                    this.notice = data.message || 'Unable to apply action.';
                                }
                            } catch (e) {
                                console.error(e);
                                this.noticeType = 'error';
                                this.notice = 'Network error while applying action.';
                            }
                            finally { this.loading = false; }
                        },
                        showCancelModal: false,
                        cancelReason: '',
                    }" class="space-y-3">

                        <div x-show="notice" x-cloak class="rounded-xl border px-4 py-3 text-sm font-semibold"
                             :class="noticeType === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-rose-200 bg-rose-50 text-rose-700'">
                            <span x-text="notice"></span>
                        </div>
                        
                        {{-- Buttons --}}
                        @if(in_array($order->status, ['assigned', 'waiting_dispatch', 'confirmed', 'pending']))
                            <button @click="performAction('accept')" :disabled="loading" class="w-full py-4 bg-blue-600 text-white rounded-2xl font-bold hover:bg-blue-700 hover:shadow-lg hover:-translate-y-0.5 transition-all disabled:opacity-50 disabled:transform-none">
                                <span x-show="!loading">Accept</span>
                                <span x-show="loading">Загрузка...</span>
                            </button>
                        @endif

                        @if(in_array($order->status, ['worker_accepted', 'assigned', 'confirmed']))
                            <button @click="performAction('en_route')" :disabled="loading" class="w-full py-4 bg-blue-600 text-white rounded-2xl font-bold hover:bg-blue-700 hover:shadow-lg hover:-translate-y-0.5 transition-all disabled:opacity-50">
                                <span x-show="!loading">En route</span>
                                <span x-show="loading">...</span>
                            </button>
                        @endif

                        @if($order->status === 'worker_en_route')
                            <button @click="performAction('at_pickup')" :disabled="loading" class="w-full py-4 bg-cyan-600 text-white rounded-2xl font-bold hover:bg-cyan-700 hover:shadow-lg hover:-translate-y-0.5 transition-all disabled:opacity-50">
                                <span x-show="!loading">At pickup</span>
                                <span x-show="loading">...</span>
                            </button>
                        @endif

                        @if($order->status === 'at_pickup')
                            <button @click="performAction('picked_up')" :disabled="loading" class="w-full py-4 bg-indigo-600 text-white rounded-2xl font-bold hover:bg-indigo-700 hover:shadow-lg hover:-translate-y-0.5 transition-all disabled:opacity-50">
                                <span x-show="!loading">Picked up</span>
                                <span x-show="loading">...</span>
                            </button>
                        @endif

                        @if(in_array($order->status, ['picked_up', 'arrived']))
                            <button @click="performAction('in_progress')" :disabled="loading" class="w-full py-4 bg-purple-600 text-white rounded-2xl font-bold hover:bg-purple-700 hover:shadow-lg hover:-translate-y-0.5 transition-all disabled:opacity-50">
                                <span x-show="!loading">In progress</span>
                                <span x-show="loading">...</span>
                            </button>
                        @endif

                        @if(in_array($order->status, ['in_progress', 'picked_up']))
                            <button @click="performAction('arrived')" :disabled="loading" class="w-full py-4 bg-emerald-500 text-white rounded-2xl font-bold hover:bg-emerald-600 hover:shadow-lg hover:-translate-y-0.5 transition-all disabled:opacity-50">
                                <span x-show="!loading">Arrived</span>
                                <span x-show="loading">...</span>
                            </button>
                        @endif

                        @if(in_array($order->status, ['in_progress', 'arrived', 'picked_up', 'assigned']))
                            @if($isRoadside)
                                <button @click="showFinishModal = true" :disabled="loading" class="w-full py-4 bg-green-600 text-white rounded-2xl font-bold hover:bg-green-700 hover:shadow-lg hover:-translate-y-0.5 transition-all disabled:opacity-50">
                                    Complete
                                </button>
                            @else
                                <button @click="performAction('completed')" :disabled="loading" class="w-full py-4 bg-green-600 text-white rounded-2xl font-bold hover:bg-green-700 hover:shadow-lg hover:-translate-y-0.5 transition-all disabled:opacity-50">
                                    Complete
                                </button>
                            @endif
                        @endif

                        @if(in_array($order->status, ['assigned', 'worker_accepted', 'waiting_dispatch']))
                            <button @click="showRejectModal = true" class="w-full py-4 bg-slate-100 text-slate-700 rounded-2xl font-bold hover:bg-amber-50 hover:text-amber-700 transition-all">
                                Reject with reason
                            </button>
                        @endif

                        @if(!in_array($order->status, ['completed', 'delivered', 'cancelled', 'disputed']))
                            <button @click="showCancelModal = true" class="w-full py-4 bg-slate-100 text-slate-700 rounded-2xl font-bold hover:bg-red-50 hover:text-red-600 transition-all">
                                Cancel
                            </button>
                            <button @click="showProblemModal = true" class="w-full py-4 bg-rose-50 text-rose-700 rounded-2xl font-bold hover:bg-rose-100 transition-all">
                                Report problem
                            </button>
                        @endif

                        {{-- Finish Modal --}}
                        <div x-show="showFinishModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="showFinishModal = false">
                            <div class="bg-white rounded-[2rem] w-full max-w-lg p-8 shadow-2xl animate-fade-in">
                                <h3 class="text-2xl font-black text-slate-900 mb-6">Отчёт о работе</h3>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Что сделано? *</label>
                                        <textarea x-model="workSummary" class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl p-4 font-medium text-slate-900 focus:border-green-500 focus:outline-none focus:ring-0 transition-colors" rows="3" placeholder="Замена колеса, подкачка..."></textarea>
                                    </div>
                                    <div class="space-y-3 bg-slate-50 p-4 rounded-2xl border border-slate-100">
                                        <label class="flex items-center gap-3 cursor-pointer">
                                            <input type="checkbox" x-model="clientOk" class="w-5 h-5 rounded-lg border-2 border-slate-300 text-green-600 focus:ring-green-500">
                                            <span class="font-bold text-slate-700">Клиент доволен / на ходу</span>
                                        </label>
                                        <label class="flex items-center gap-3 cursor-pointer">
                                            <input type="checkbox" x-model="vehicleMoved" class="w-5 h-5 rounded-lg border-2 border-slate-300 text-green-600 focus:ring-green-500">
                                            <span class="font-bold text-slate-700">Машина эвакуирована</span>
                                        </label>
                                    </div>
                                    <div class="flex gap-3 pt-4">
                                        <button @click="showFinishModal = false" class="flex-1 py-3 bg-slate-100 text-slate-700 rounded-xl font-bold hover:bg-slate-200">Отмена</button>
                                        <button @click="performAction('completed', null, { work_summary: workSummary, client_ok: clientOk, vehicle_moved: vehicleMoved }); showFinishModal = false" 
                                                :disabled="!workSummary.trim()" 
                                                class="flex-1 py-3 bg-green-600 text-white rounded-xl font-bold hover:bg-green-700 disabled:opacity-50">
                                            Отправить
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div x-show="showRejectModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="showRejectModal = false">
                            <div class="bg-white rounded-[2rem] w-full max-w-lg p-8 shadow-2xl">
                                <h3 class="text-2xl font-black text-slate-900 mb-6">Reject assignment</h3>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Reason *</label>
                                <textarea x-model="rejectReason" class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl p-4 font-medium text-slate-900 focus:border-amber-500 focus:outline-none focus:ring-0 transition-colors" rows="3"></textarea>
                                <div class="flex gap-3 pt-6">
                                    <button @click="showRejectModal = false" class="flex-1 py-3 bg-slate-100 text-slate-700 rounded-xl font-bold hover:bg-slate-200">Back</button>
                                    <button @click="performAction('reject', rejectReason); showRejectModal = false" :disabled="!rejectReason.trim()" class="flex-1 py-3 bg-amber-600 text-white rounded-xl font-bold hover:bg-amber-700 disabled:opacity-50">Confirm</button>
                                </div>
                            </div>
                        </div>

                        {{-- Cancel Modal --}}
                        <div x-show="showCancelModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="showCancelModal = false">
                            <div class="bg-white rounded-[2rem] w-full max-w-lg p-8 shadow-2xl animate-fade-in">
                                <h3 class="text-2xl font-black text-slate-900 mb-6">Отмена заказа</h3>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Причина *</label>
                                    <textarea x-model="cancelReason" class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl p-4 font-medium text-slate-900 focus:border-red-500 focus:outline-none focus:ring-0 transition-colors" rows="3" placeholder="Клиент не вышел на связь..."></textarea>
                                </div>
                                <div class="flex gap-3 pt-6">
                                    <button @click="showCancelModal = false" class="flex-1 py-3 bg-slate-100 text-slate-700 rounded-xl font-bold hover:bg-slate-200">Назад</button>
                                    <button @click="performAction('cancel', cancelReason); showCancelModal = false" 
                                            :disabled="!cancelReason.trim()" 
                                            class="flex-1 py-3 bg-red-600 text-white rounded-xl font-bold hover:bg-red-700 disabled:opacity-50">
                                        Подтвердить отмену
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div x-show="showProblemModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="showProblemModal = false">
                            <div class="bg-white rounded-[2rem] w-full max-w-lg p-8 shadow-2xl">
                                <h3 class="text-2xl font-black text-slate-900 mb-6">Report problem</h3>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Problem note *</label>
                                <textarea x-model="problemReason" class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl p-4 font-medium text-slate-900 focus:border-rose-500 focus:outline-none focus:ring-0 transition-colors" rows="3"></textarea>
                                <div class="flex gap-3 pt-6">
                                    <button @click="showProblemModal = false" class="flex-1 py-3 bg-slate-100 text-slate-700 rounded-xl font-bold hover:bg-slate-200">Back</button>
                                    <button @click="performAction('report_problem', problemReason); showProblemModal = false" :disabled="!problemReason.trim()" class="flex-1 py-3 bg-rose-600 text-white rounded-xl font-bold hover:bg-rose-700 disabled:opacity-50">Send</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Timeline --}}
                @if(count($timeline) > 0)
                    <div class="bg-white rounded-[2.5rem] p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100">
                        <h2 class="text-xl font-black text-slate-900 mb-6 tracking-tight">История</h2>
                        <div class="relative space-y-6 pl-4 border-l-2 border-slate-100">
                            @foreach($timeline as $event)
                                <div class="relative">
                                    <div class="absolute -left-[21px] top-1.5 w-3 h-3 rounded-full bg-slate-200 ring-4 ring-white"></div>
                                    <div class="text-sm font-bold text-slate-900">{{ $event['label'] }}</div>
                                    <div class="text-xs font-medium text-slate-400 mt-0.5">{{ $event['timestamp']->format('d.m.Y H:i') }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @else
        <div class="bg-white rounded-[2.5rem] p-12 text-center shadow-sm border border-slate-100">
            <h3 class="text-xl font-black text-slate-900 mb-2">Заказ не найден</h3>
            <p class="text-slate-500 font-medium">Возможно, он был удален или у вас нет прав на просмотр.</p>
        </div>
    @endif
</div>
@endsection
