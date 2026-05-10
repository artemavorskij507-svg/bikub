@extends('lk.layout')

@section('title', 'Задание #' . $assignment->id)

@section('content')
<div class="space-y-10" data-scroll-container>
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-50 border border-amber-100 text-xs font-bold uppercase tracking-widest text-amber-600 mb-3">
                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                Детали задания
            </div>
            <h1 class="text-4xl font-black text-slate-900 tracking-tight">Задание #{{ $assignment->id }}</h1>
            <div class="flex items-center gap-3 mt-2">
                @php
                    $statusConfig = [
                        'proposed' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'label' => 'Предложено'],
                        'accepted' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'label' => 'Принято'],
                        'declined' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'label' => 'Отклонено'],
                        'in_progress' => ['bg' => 'bg-sky-100', 'text' => 'text-sky-800', 'label' => 'В работе'],
                        'started' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-800', 'label' => 'Начато'],
                        'completed' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-800', 'label' => 'Завершено'],
                    ];
                    $st = $statusConfig[$assignment->status] ?? ['bg' => 'bg-slate-100', 'text' => 'text-slate-800', 'label' => $assignment->status];
                @endphp
                <span class="px-3 py-1 rounded-lg text-xs font-black uppercase tracking-wider {{ $st['bg'] }} {{ $st['text'] }}">
                    {{ $st['label'] }}
                </span>
            </div>
        </div>
        <a href="{{ route('lk.executor.jobs.index') }}" class="group inline-flex items-center gap-2 px-6 py-3 bg-white border border-slate-200 rounded-2xl font-bold text-slate-600 hover:bg-slate-50 hover:border-slate-300 hover:text-slate-800 transition-all shadow-sm">
            <svg class="w-5 h-5 transition-transform group-hover:-translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            Назад к списку
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-2 space-y-8">
            {{-- Информация о заказе --}}
            <div class="bg-white rounded-[2.5rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 p-8">
                <h2 class="text-xl font-black text-slate-900 mb-6 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    Информация о заказе
                </h2>
                
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl border border-slate-100">
                        <span class="font-bold text-slate-500">Номер заказа</span>
                        <span class="font-black text-slate-900">#{{ $assignment->order->order_number ?? $assignment->order->id }}</span>
                    </div>
                    
                    <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl border border-slate-100">
                        <span class="font-bold text-slate-500">Тип услуги</span>
                        <span class="font-bold text-slate-900">
                            @php
                                $serviceType = \App\Enums\ServiceType::tryFrom($assignment->order->service_type);
                            @endphp
                            {{ $serviceType ? $serviceType->label() : $assignment->order->service_type }}
                        </span>
                    </div>

                    @if($assignment->order->estimated_total)
                        <div class="flex items-center justify-between p-4 bg-emerald-50 rounded-2xl border border-emerald-100">
                            <span class="font-bold text-emerald-700">Оценочная стоимость</span>
                            <span class="font-black text-emerald-900">{{ number_format($assignment->order->estimated_total / 100, 2) }} NOK</span>
                        </div>
                    @endif
                </div>
            </div>

            @if($assignment->order->handymanDetails)
                {{-- Описание задачи --}}
                <div class="bg-white rounded-[2.5rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 p-8">
                    <h2 class="text-xl font-black text-slate-900 mb-6 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-purple-50 flex items-center justify-center text-purple-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        Описание задачи
                    </h2>
                    
                    <div class="space-y-6">
                        <div>
                            <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Описание</div>
                            <div class="bg-slate-50 rounded-2xl p-5 text-slate-900 font-medium whitespace-pre-wrap leading-relaxed border border-slate-100">{{ $assignment->order->handymanDetails->description }}</div>
                        </div>
                        
                        @if($assignment->order->handymanDetails->context_notes)
                            <div>
                                <div class="text-xs font-bold text-amber-500 uppercase tracking-wider mb-2">Заметки</div>
                                <div class="bg-amber-50 rounded-2xl p-5 text-amber-900 font-medium whitespace-pre-wrap leading-relaxed border border-amber-100">{{ $assignment->order->handymanDetails->context_notes }}</div>
                            </div>
                        @endif
                        
                        @if($assignment->order->handymanDetails->expected_duration_minutes)
                            <div class="flex items-center gap-2 text-sm font-bold text-slate-500">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                Ожидаемая длительность: <span class="text-slate-900">{{ $assignment->order->handymanDetails->expected_duration_minutes }} мин</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Адрес --}}
                <div class="bg-white rounded-[2.5rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 p-8">
                    <h2 class="text-xl font-black text-slate-900 mb-6 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center text-green-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        </div>
                        Адрес
                    </h2>
                    <div class="pl-4 border-l-2 border-green-100">
                        <div class="text-lg font-bold text-slate-900">{{ $assignment->order->handymanDetails->address_line }}</div>
                        <div class="text-slate-500 font-medium mt-1">{{ $assignment->order->handymanDetails->postal_code }} {{ $assignment->order->handymanDetails->city }}</div>
                    </div>
                </div>

                {{-- Желаемое время --}}
                @if($assignment->order->handymanDetails->desired_start_at || $assignment->order->handymanDetails->desired_finish_at)
                    <div class="bg-white rounded-[2.5rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 p-8">
                        <h2 class="text-xl font-black text-slate-900 mb-6 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            Желаемое время
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @if($assignment->order->handymanDetails->desired_start_at)
                                <div class="p-4 bg-blue-50 rounded-2xl border border-blue-100">
                                    <div class="text-xs font-bold text-blue-600 uppercase tracking-wider mb-1">Начало</div>
                                    <div class="text-lg font-black text-blue-900">{{ $assignment->order->handymanDetails->desired_start_at->format('d.m.Y H:i') }}</div>
                                </div>
                            @endif
                            @if($assignment->order->handymanDetails->desired_finish_at)
                                <div class="p-4 bg-blue-50 rounded-2xl border border-blue-100">
                                    <div class="text-xs font-bold text-blue-600 uppercase tracking-wider mb-1">Окончание</div>
                                    <div class="text-lg font-black text-blue-900">{{ $assignment->order->handymanDetails->desired_finish_at->format('d.m.Y H:i') }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            @endif
        </div>

        <div class="space-y-8">
            {{-- Действия --}}
            <div class="bg-white rounded-[2.5rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 p-8 sticky top-6">
                <h2 class="text-xl font-black text-slate-900 mb-6 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>
                    Действия
                </h2>
                
                <div class="flex flex-col gap-3">
                    @if($assignment->status === 'proposed')
                        <form method="POST" action="{{ route('lk.executor.jobs.accept', $assignment) }}">
                            @csrf
                            <button type="submit" class="w-full py-4 bg-green-600 text-white rounded-2xl font-bold hover:bg-green-700 hover:shadow-lg hover:-translate-y-0.5 transition-all">
                                ✓ Принять задачу
                            </button>
                        </form>
                        <form method="POST" action="{{ route('lk.executor.jobs.decline', $assignment) }}">
                            @csrf
                            <button type="submit" class="w-full py-4 bg-white border-2 border-red-100 text-red-600 rounded-2xl font-bold hover:bg-red-50 hover:border-red-200 transition-all">
                                ✕ Отклонить
                            </button>
                        </form>
                    @endif

                    @if(in_array($assignment->status, ['accepted', 'in_progress']))
                        @if($assignment->status === 'accepted')
                            <form method="POST" action="{{ route('lk.executor.jobs.status', $assignment) }}">
                                @csrf
                                <input type="hidden" name="status" value="in_route">
                                <button type="submit" class="w-full py-4 bg-blue-600 text-white rounded-2xl font-bold hover:bg-blue-700 hover:shadow-lg hover:-translate-y-0.5 transition-all">
                                    🚗 Выехал
                                </button>
                            </form>
                        @endif
                        @if(!$assignment->actual_start_at)
                            <form method="POST" action="{{ route('lk.executor.jobs.status', $assignment) }}">
                                @csrf
                                <input type="hidden" name="status" value="started">
                                <button type="submit" class="w-full py-4 bg-purple-600 text-white rounded-2xl font-bold hover:bg-purple-700 hover:shadow-lg hover:-translate-y-0.5 transition-all">
                                    ▶ Начать работу
                                </button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('lk.executor.jobs.status', $assignment) }}">
                            @csrf
                            <input type="hidden" name="status" value="finished">
                            <button type="submit" class="w-full py-4 bg-green-600 text-white rounded-2xl font-bold hover:bg-green-700 hover:shadow-lg hover:-translate-y-0.5 transition-all">
                                ✓ Завершить
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- Временные метки --}}
            <div class="bg-white rounded-[2.5rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 p-8">
                <h2 class="text-xl font-black text-slate-900 mb-6 flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    Хронология
                </h2>
                <div class="space-y-4">
                    @if($assignment->planned_start_at)
                        <div class="flex justify-between items-center p-3 bg-slate-50 rounded-xl">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">План. старт</span>
                            <span class="font-bold text-slate-900">{{ $assignment->planned_start_at->format('d.m H:i') }}</span>
                        </div>
                    @endif
                    @if($assignment->planned_finish_at)
                        <div class="flex justify-between items-center p-3 bg-slate-50 rounded-xl">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">План. финиш</span>
                            <span class="font-bold text-slate-900">{{ $assignment->planned_finish_at->format('d.m H:i') }}</span>
                        </div>
                    @endif
                    @if($assignment->actual_start_at)
                        <div class="flex justify-between items-center p-3 bg-green-50 rounded-xl border border-green-100">
                            <span class="text-xs font-bold text-green-700 uppercase tracking-wider">Старт</span>
                            <span class="font-black text-green-900">{{ $assignment->actual_start_at->format('d.m H:i') }}</span>
                        </div>
                    @endif
                    @if($assignment->actual_finish_at)
                        <div class="flex justify-between items-center p-3 bg-emerald-50 rounded-xl border border-emerald-100">
                            <span class="text-xs font-bold text-emerald-700 uppercase tracking-wider">Финиш</span>
                            <span class="font-black text-emerald-900">{{ $assignment->actual_finish_at->format('d.m H:i') }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection