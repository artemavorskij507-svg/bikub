@extends('lk.layout')

@section('title', 'Дорожное задание #' . $job->id)

@section('content')
<div class="space-y-8" data-scroll-container>
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-red-50 border border-red-100 text-xs font-bold uppercase tracking-widest text-red-600 mb-3">
                <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                Roadside Assist
            </div>
            <h1 class="text-4xl font-black text-slate-900 tracking-tight">Задание #{{ $job->id }}</h1>
            <p class="text-slate-500 font-medium mt-2">Детали вызова экстренной помощи</p>
        </div>
        <a href="{{ route('lk.roadside-jobs.index') }}" class="group flex items-center gap-2 px-5 py-2.5 bg-white/80 backdrop-blur-sm border border-slate-200 rounded-xl text-slate-600 font-bold hover:bg-white hover:shadow-lg transition-all w-fit">
            <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            Назад к списку
        </a>
    </div>

    @if(session('status'))
        <div class="p-4 rounded-xl bg-green-50 border border-green-200 flex items-center gap-3 text-green-700 shadow-sm animate-fade-in">
            <svg class="w-6 h-6 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span class="font-medium">{{ session('status') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Main Content (Left Column) --}}
        <div class="lg:col-span-2 space-y-8">
            {{-- Status & Meta --}}
            <div class="bg-white/80 backdrop-blur-xl rounded-[2rem] shadow-lg border border-white/50 p-8 relative overflow-hidden">
                <div class="absolute top-0 right-0 p-8 opacity-5 pointer-events-none">
                    <svg class="w-64 h-64" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
                </div>

                <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-8">
                    <div>
                        <h2 class="text-2xl font-black text-slate-900 mb-2">Статус задания</h2>
                        @php
                            $statusLabels = [
                                'new' => '🔥 Новый', 'assigned' => '👤 Назначен', 'on_route' => '🚗 В пути',
                                'on_spot' => '📍 На месте', 'in_progress' => '🔧 В работе', 'completed' => '✅ Завершен',
                                'cancelled' => '❌ Отменен', 'rejected' => '⛔ Отклонен', 'failed' => '⚠️ Не выполнен',
                            ];
                            $statusColors = [
                                'new' => 'bg-red-100 text-red-700 border-red-200',
                                'assigned' => 'bg-blue-100 text-blue-700 border-blue-200',
                                'on_route' => 'bg-indigo-100 text-indigo-700 border-indigo-200',
                                'on_spot' => 'bg-purple-100 text-purple-700 border-purple-200',
                                'in_progress' => 'bg-amber-100 text-amber-700 border-amber-200',
                                'completed' => 'bg-green-100 text-green-700 border-green-200',
                                'cancelled' => 'bg-slate-100 text-slate-700 border-slate-200',
                                'rejected' => 'bg-slate-100 text-slate-700 border-slate-200',
                                'failed' => 'bg-slate-100 text-slate-700 border-slate-200',
                            ];
                            $status = $job->status;
                        @endphp
                        <span class="inline-flex px-4 py-2 rounded-xl text-sm font-black uppercase tracking-widest border {{ $statusColors[$status] ?? 'bg-slate-100 text-slate-600' }}">
                            {{ $statusLabels[$status] ?? $status }}
                        </span>
                    </div>
                    <div class="text-right">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Дата создания</p>
                        <p class="text-lg font-bold text-slate-900">{{ $job->created_at->format('d.m.Y H:i') }}</p>
                    </div>
                </div>

                @if($job->order)
                    <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Связанный заказ</p>
                            <p class="font-bold text-slate-900">#{{ $job->order->order_number ?? $job->order->id }}</p>
                        </div>
                        <a href="{{ route('lk.orders.show', $job->order->id) }}" class="text-sm font-bold text-blue-600 hover:text-blue-700 hover:underline">Открыть заказ →</a>
                    </div>
                @endif
            </div>

            {{-- Incident Details --}}
            <div class="grid md:grid-cols-2 gap-6">
                {{-- Vehicle --}}
                <div class="bg-white/80 backdrop-blur-xl rounded-[2rem] shadow-lg border border-white/50 p-8">
                    <h3 class="text-lg font-black text-slate-900 mb-6 flex items-center gap-2">
                        <svg class="w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                        Транспорт
                    </h3>
                    <div class="space-y-4">
                        @if($job->metadata['vehicle_make'] ?? null)
                            <div><p class="text-xs font-bold text-slate-400 uppercase">Марка</p><p class="font-bold text-slate-900 text-lg">{{ $job->metadata['vehicle_make'] }}</p></div>
                        @endif
                        @if($job->metadata['vehicle_model'] ?? null)
                            <div><p class="text-xs font-bold text-slate-400 uppercase">Модель</p><p class="font-bold text-slate-900 text-lg">{{ $job->metadata['vehicle_model'] }}</p></div>
                        @endif
                        @if($job->metadata['vehicle_plate'] ?? null)
                            <div><p class="text-xs font-bold text-slate-400 uppercase">Госномер</p><p class="font-mono font-bold text-slate-900 bg-slate-100 px-2 py-1 rounded inline-block">{{ $job->metadata['vehicle_plate'] }}</p></div>
                        @endif
                        @if(!($job->metadata['vehicle_make'] ?? null) && !($job->metadata['vehicle_plate'] ?? null))
                            <p class="text-slate-400 italic">Информация об авто отсутствует</p>
                        @endif
                    </div>
                </div>

                {{-- Location --}}
                <div class="bg-white/80 backdrop-blur-xl rounded-[2rem] shadow-lg border border-white/50 p-8">
                    <h3 class="text-lg font-black text-slate-900 mb-6 flex items-center gap-2">
                        <svg class="w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                        Локация
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase">Адрес</p>
                            <p class="font-medium text-slate-900 leading-snug">{{ $job->metadata['location_text'] ?? 'Не указан' }}</p>
                        </div>
                        @if($job->lat && $job->lng)
                            <div>
                                <p class="text-xs font-bold text-slate-400 uppercase">Координаты</p>
                                <p class="font-mono text-sm text-slate-600 mb-2">{{ number_format($job->lat, 6) }}, {{ number_format($job->lng, 6) }}</p>
                                <a href="https://www.google.com/maps/search/?api=1&query={{ $job->lat }},{{ $job->lng }}" target="_blank" class="inline-flex items-center gap-2 text-sm font-bold text-blue-600 hover:text-blue-700 bg-blue-50 hover:bg-blue-100 px-4 py-2 rounded-xl transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                    Открыть карту
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Client Info --}}
            @if($job->order && $job->order->user)
                <div class="bg-white/80 backdrop-blur-xl rounded-[2rem] shadow-lg border border-white/50 p-8">
                    <h3 class="text-lg font-black text-slate-900 mb-6 flex items-center gap-2">
                        <svg class="w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        Клиент
                    </h3>
                    <div class="flex items-center gap-6">
                        <div class="w-16 h-16 rounded-full bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center text-slate-400 font-black text-2xl">
                            {{ substr($job->order->user->name, 0, 1) }}
                        </div>
                        <div>
                            <p class="font-bold text-slate-900 text-xl">{{ $job->order->user->name }}</p>
                            <div class="flex flex-wrap gap-4 mt-2">
                                @if($job->order->user->phone)
                                    <a href="tel:{{ $job->order->user->phone }}" class="flex items-center gap-2 text-sm font-bold text-slate-600 hover:text-blue-600 transition-colors">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                        {{ $job->order->user->phone }}
                                    </a>
                                @endif
                                @if($job->order->user->email)
                                    <span class="flex items-center gap-2 text-sm font-medium text-slate-400">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                        {{ $job->order->user->email }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Timeline --}}
            <div class="bg-white/80 backdrop-blur-xl rounded-[2rem] shadow-lg border border-white/50 p-8">
                <h3 class="text-lg font-black text-slate-900 mb-6">Хронология</h3>
                <div class="relative pl-8 border-l-2 border-slate-100 space-y-8">
                    @php
                        $timelineSteps = [
                            ['key' => 'created_at', 'label' => 'Задание создано', 'timestamp' => $timeline['created_at'], 'icon' => 'plus'],
                            ['key' => 'assigned_at', 'label' => 'Назначено исполнителю', 'timestamp' => $timeline['assigned_at'], 'icon' => 'user'],
                            ['key' => 'en_route_at', 'label' => 'Исполнитель выехал', 'timestamp' => $timeline['en_route_at'], 'icon' => 'truck'],
                            ['key' => 'on_spot_at', 'label' => 'Прибытие на место', 'timestamp' => $timeline['on_spot_at'], 'icon' => 'map-pin'],
                            ['key' => 'started_at', 'label' => 'Начало работ', 'timestamp' => $timeline['started_at'], 'icon' => 'wrench'],
                            ['key' => 'completed_at', 'label' => 'Завершено', 'timestamp' => $timeline['completed_at'], 'icon' => 'check'],
                            ['key' => 'cancelled_at', 'label' => 'Отменено', 'timestamp' => $timeline['cancelled_at'], 'icon' => 'x'],
                        ];
                    @endphp

                    @foreach($timelineSteps as $step)
                        @if($step['timestamp'])
                            <div class="relative group">
                                <span class="absolute -left-[2.4rem] top-1 w-8 h-8 rounded-full bg-slate-900 border-4 border-slate-50 flex items-center justify-center text-white shadow-sm z-10 group-hover:scale-110 transition-transform">
                                    {{-- Simple dot or icon based on step --}}
                                    <div class="w-2 h-2 rounded-full bg-white"></div>
                                </span>
                                <div>
                                    <p class="font-bold text-slate-900">{{ $step['label'] }}</p>
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">
                                        {{ is_string($step['timestamp']) ? \Carbon\Carbon::parse($step['timestamp'])->format('d.m.Y H:i') : $step['timestamp']->format('d.m.Y H:i') }}
                                    </p>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Actions (Right Column) --}}
        <div class="space-y-6">
            <div class="sticky top-6">
                <div class="bg-white/80 backdrop-blur-xl rounded-[2rem] shadow-lg border border-white/50 p-6">
                    <h3 class="text-lg font-black text-slate-900 mb-6">Действия</h3>
                    
                    @if($job->status === 'new' && !$job->helper && (!$job->order || !$job->order->assigned_to))
                        <form method="POST" action="{{ route('lk.roadside-jobs.action', $job) }}" class="space-y-4">
                            @csrf
                            <input type="hidden" name="action" value="accept">
                            <button type="submit" class="w-full py-4 bg-gradient-to-r from-emerald-500 to-teal-500 text-white rounded-xl font-bold shadow-lg shadow-emerald-200 hover:shadow-xl hover:-translate-y-1 transition-all">
                                Принять задание
                            </button>
                        </form>
                        
                        <form method="POST" action="{{ route('lk.roadside-jobs.action', $job) }}" class="space-y-4 mt-4" x-data="{ open: false }">
                            @csrf
                            <input type="hidden" name="action" value="reject">
                            <button type="button" @click="open = !open" class="w-full py-3 bg-white border-2 border-slate-200 text-slate-600 rounded-xl font-bold hover:bg-slate-50 transition-colors">
                                Отклонить
                            </button>
                            <div x-show="open" x-collapse class="space-y-3">
                                <textarea name="reason" rows="3" class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-slate-400 focus:ring-0 transition-all text-sm font-medium" placeholder="Укажите причину..."></textarea>
                                <button type="submit" class="w-full py-3 bg-red-50 text-red-600 rounded-xl font-bold hover:bg-red-100 transition-colors">
                                    Подтвердить отказ
                                </button>
                            </div>
                        </form>

                    @elseif(in_array($job->status, ['assigned', 'new']))
                        <form method="POST" action="{{ route('lk.roadside-jobs.action', $job) }}" class="space-y-4">
                            @csrf
                            <input type="hidden" name="action" value="start_travel">
                            <button type="submit" class="w-full py-4 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl font-bold shadow-lg shadow-blue-200 hover:shadow-xl hover:-translate-y-1 transition-all">
                                🚀 Выезжаю
                            </button>
                        </form>

                    @elseif($job->status === 'on_route')
                        <form method="POST" action="{{ route('lk.roadside-jobs.action', $job) }}" class="space-y-4">
                            @csrf
                            <input type="hidden" name="action" value="arrived">
                            <button type="submit" class="w-full py-4 bg-gradient-to-r from-purple-600 to-fuchsia-600 text-white rounded-xl font-bold shadow-lg shadow-purple-200 hover:shadow-xl hover:-translate-y-1 transition-all">
                                📍 Я на месте
                            </button>
                        </form>

                    @elseif($job->status === 'on_spot')
                        <form method="POST" action="{{ route('lk.roadside-jobs.action', $job) }}" class="space-y-4">
                            @csrf
                            <input type="hidden" name="action" value="start_job">
                            <button type="submit" class="w-full py-4 bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-xl font-bold shadow-lg shadow-amber-200 hover:shadow-xl hover:-translate-y-1 transition-all">
                                🔧 Начать работу
                            </button>
                        </form>

                    @elseif($job->status === 'in_progress')
                        <form method="POST" action="{{ route('lk.roadside-jobs.action', $job) }}" class="space-y-4">
                            @csrf
                            <input type="hidden" name="action" value="finish_job">
                            <button type="submit" class="w-full py-4 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-xl font-bold shadow-lg shadow-green-200 hover:shadow-xl hover:-translate-y-1 transition-all">
                                ✅ Завершить
                            </button>
                        </form>
                    @endif

                    @if(!in_array($job->status, ['completed', 'cancelled', 'rejected', 'failed']))
                         <form method="POST" action="{{ route('lk.roadside-jobs.action', $job) }}" class="mt-6 pt-6 border-t border-slate-100" x-data="{ open: false }">
                            @csrf
                            <input type="hidden" name="action" value="cancel">
                            <button type="button" @click="open = !open" x-show="!open" class="w-full text-red-500 font-bold text-sm hover:text-red-600 hover:bg-red-50 py-2 rounded-lg transition-colors">
                                Отменить задание
                            </button>
                            <div x-show="open" x-collapse class="space-y-3">
                                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider">Причина отмены</label>
                                <textarea name="reason" rows="3" class="w-full px-4 py-3 rounded-xl border border-slate-200 bg-slate-50 focus:bg-white focus:border-slate-400 focus:ring-0 transition-all text-sm font-medium" placeholder="Опишите причину..."></textarea>
                                <div class="flex gap-2">
                                    <button type="button" @click="open = false" class="flex-1 py-2 bg-slate-100 text-slate-600 rounded-lg font-bold text-sm">Отмена</button>
                                    <button type="submit" class="flex-1 py-2 bg-red-500 text-white rounded-lg font-bold text-sm shadow-lg shadow-red-200">Подтвердить</button>
                                </div>
                            </div>
                        </form>
                    @endif

                     @if(in_array($job->status, ['completed', 'cancelled', 'rejected', 'failed']))
                        <div class="mt-4 p-4 bg-slate-50 rounded-xl text-center border border-slate-100">
                            <p class="text-slate-500 font-medium">Действия недоступны</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection