@extends('lk.layout')

@section('title', 'Дорожные задания')

@section('content')
<div class="space-y-10" data-scroll-container>
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-red-50 border border-red-100 text-xs font-bold uppercase tracking-widest text-red-600 mb-3">
                <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                Roadside Assist
            </div>
            <h1 class="text-4xl font-black text-slate-900 tracking-tight">Дорожные задания</h1>
            <p class="text-slate-500 font-medium mt-2">Управление вызовами экстренной помощи на дороге</p>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="flex p-1 bg-slate-100 rounded-2xl w-full md:w-fit">
        <a href="{{ route('lk.roadside-jobs.index', ['filter' => 'active']) }}" 
           class="flex-1 md:flex-none px-6 py-2.5 rounded-xl text-sm font-bold transition-all {{ $filter === 'active' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
            🔥 Активные
            @if($activeJobs->count() > 0)
                <span class="ml-2 px-2 py-0.5 bg-red-100 text-red-600 rounded-lg text-xs">{{ $activeJobs->count() }}</span>
            @endif
        </a>
        <a href="{{ route('lk.roadside-jobs.index', ['filter' => 'completed']) }}" 
           class="flex-1 md:flex-none px-6 py-2.5 rounded-xl text-sm font-bold transition-all {{ $filter === 'completed' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
            📋 История
            @if($completedJobs->count() > 0)
                <span class="ml-2 px-2 py-0.5 bg-slate-200 text-slate-600 rounded-lg text-xs">{{ $completedJobs->count() }}</span>
            @endif
        </a>
    </div>

    {{-- Active Jobs --}}
    @if($filter === 'active' || $filter === 'all')
        <div>
            @if($activeJobs->isEmpty())
                <div class="bg-white rounded-[2.5rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 p-16 text-center">
                    <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6 text-slate-300">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                    </div>
                    <h3 class="text-2xl font-black text-slate-900 mb-2">Все спокойно</h3>
                    <p class="text-slate-500 font-medium">Активных вызовов пока нет</p>
                </div>
            @else
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach($activeJobs as $job)
                        <div class="group bg-white rounded-3xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 hover:border-red-200 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl relative overflow-hidden">
                            <div class="absolute top-0 left-0 bottom-0 w-1.5 bg-red-500"></div>
                            
                            <div class="pl-4">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Задание #{{ $job->id }}</div>
                                        @php
                                            $incidentTypes = [
                                                'jump_start' => '🔋 Прикурить', 'fuel' => '⛽ Топливо', 'flat_tire' => '🛞 Прокол',
                                                'locked_keys' => '🔑 Ключи', 'engine_no_start' => '🚗 Не заводится',
                                                'tow_needed' => '🚛 Эвакуатор', 'accident' => '⚠️ ДТП',
                                            ];
                                        @endphp
                                        <h3 class="text-lg font-black text-slate-900 group-hover:text-red-600 transition-colors">{{ $incidentTypes[$job->incident_type] ?? 'Помощь' }}</h3>
                                    </div>
                                    @php
                                        $statusConfig = [
                                            'new' => ['bg' => 'bg-red-100', 'text' => 'text-red-700'],
                                            'assigned' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700'],
                                            'on_route' => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-700'],
                                            'on_spot' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-700'],
                                            'in_progress' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700'],
                                        ];
                                        $st = $statusConfig[$job->status] ?? ['bg' => 'bg-slate-100', 'text' => 'text-slate-700'];
                                    @endphp
                                    <span class="px-3 py-1 rounded-lg text-xs font-bold uppercase tracking-wider {{ $st['bg'] }} {{ $st['text'] }}">
                                        {{ $job->status }}
                                    </span>
                                </div>

                                <div class="mb-6">
                                    <div class="flex items-start gap-2 text-sm font-medium text-slate-600 mb-2">
                                        <svg class="w-5 h-5 text-red-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                        <span class="line-clamp-2">{{ $job->metadata['location_text'] ?? ($job->lat ? $job->lat.', '.$job->lng : 'Адрес не указан') }}</span>
                                    </div>
                                    @if($job->order)
                                        <div class="flex items-center gap-2 text-xs font-bold text-slate-400 uppercase tracking-wider">
                                            <span>Заказ #{{ $job->order->order_number ?? $job->order->id }}</span>
                                        </div>
                                    @endif
                                </div>

                                <a href="{{ route('lk.roadside-jobs.show', $job) }}" class="block w-full py-3 bg-slate-900 text-white rounded-xl text-center font-bold hover:bg-black hover:shadow-lg transition-all">
                                    Открыть детали
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    {{-- History --}}
    @if($filter === 'completed' || $filter === 'all')
        <div>
            @if($completedJobs->isEmpty())
                <div class="bg-white rounded-[2.5rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 p-16 text-center">
                    <p class="text-slate-400 font-medium">История пуста</p>
                </div>
            @else
                <div class="bg-white rounded-[2.5rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-slate-100 text-xs font-bold text-slate-400 uppercase tracking-wider">
                                    <th class="p-6">ID</th>
                                    <th class="p-6">Тип</th>
                                    <th class="p-6">Адрес</th>
                                    <th class="p-6">Статус</th>
                                    <th class="p-6">Дата</th>
                                    <th class="p-6"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-sm font-medium text-slate-700">
                                @foreach($completedJobs as $job)
                                    <tr class="hover:bg-slate-50 transition-colors group">
                                        <td class="p-6 text-slate-900 font-bold">#{{ $job->id }}</td>
                                        <td class="p-6">
                                            @php $typeLabel = $incidentTypes[$job->incident_type] ?? 'Помощь'; @endphp
                                            {{ $typeLabel }}
                                        </td>
                                        <td class="p-6 max-w-xs truncate">{{ $job->metadata['location_text'] ?? '—' }}</td>
                                        <td class="p-6">
                                            <span class="px-3 py-1 rounded-lg text-xs font-bold uppercase tracking-wider bg-slate-100 text-slate-600">
                                                {{ $job->status }}
                                            </span>
                                        </td>
                                        <td class="p-6 text-slate-500">{{ $job->created_at->format('d.m.Y') }}</td>
                                        <td class="p-6 text-right">
                                            <a href="{{ route('lk.roadside-jobs.show', $job) }}" class="text-blue-600 hover:text-blue-800 font-bold opacity-0 group-hover:opacity-100 transition-opacity">Открыть →</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
@endsection