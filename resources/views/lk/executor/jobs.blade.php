@extends('lk.layout')

@section('title', 'Задания мастера')

@section('content')
<div class="space-y-10" data-scroll-container>
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-50 border border-amber-100 text-xs font-bold uppercase tracking-widest text-amber-600 mb-3">
                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                Задания
            </div>
            <h1 class="text-4xl font-black text-slate-900 tracking-tight">Задания мастера</h1>
            <p class="text-slate-500 font-medium mt-2">Управление вашими текущими и новыми заданиями</p>
        </div>
    </div>

    @if($assignments->isEmpty())
        <div class="bg-white rounded-[2.5rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 p-16 text-center">
            <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6 text-slate-300">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
            </div>
            <h3 class="text-2xl font-black text-slate-900 mb-2">Нет активных заданий</h3>
            <p class="text-slate-500 font-medium">Вам пока не назначены задания. Ожидайте новых заявок.</p>
        </div>
    @else
        <div class="grid grid-cols-1 gap-6">
            @foreach($assignments as $assignment)
                <div class="group relative bg-white rounded-3xl p-6 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 hover:border-amber-200 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl overflow-hidden">
                    <div class="absolute top-0 left-0 bottom-0 w-1.5 transition-colors duration-300
                        {{ $assignment->status === 'completed' ? 'bg-emerald-500' : ($assignment->status === 'in_progress' ? 'bg-amber-500' : 'bg-slate-200') }}">
                    </div>

                    <div class="pl-4 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                        <div class="flex-1">
                            <div class="flex items-center gap-4 mb-4">
                                <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-amber-600 bg-amber-50 shadow-sm transition-transform group-hover:scale-110">
                                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-0.5">Задание #{{ $assignment->id }}</div>
                                    <h3 class="text-xl font-black text-slate-900 group-hover:text-amber-600 transition-colors">
                                        Заказ #{{ $assignment->order->order_number ?? $assignment->order_id }}
                                    </h3>
                                </div>
                            </div>

                            @if($assignment->order->handymanDetails)
                                <div class="mb-4 space-y-2">
                                    <div class="flex items-start gap-2 text-sm font-bold text-slate-800">
                                        <svg class="w-5 h-5 text-amber-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                        <span>{{ $assignment->order->handymanDetails->address_line }}, {{ $assignment->order->handymanDetails->city }}</span>
                                    </div>
                                    @if($assignment->order->handymanDetails->description)
                                        <p class="text-sm font-medium text-slate-500 line-clamp-2 pl-7">{{ Str::limit($assignment->order->handymanDetails->description, 100) }}</p>
                                    @endif
                                </div>
                            @endif

                            <div class="flex items-center gap-3">
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
                                <span class="px-3 py-1.5 rounded-lg text-xs font-black uppercase tracking-wider {{ $st['bg'] }} {{ $st['text'] }}">
                                    {{ $st['label'] }}
                                </span>
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">{{ $assignment->created_at->diffForHumans() }}</span>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row gap-3 min-w-[200px]">
                            <a href="{{ route('lk.executor.jobs.show', $assignment) }}" class="flex-1 py-3 px-6 bg-slate-100 text-slate-700 rounded-xl text-sm font-bold text-center hover:bg-slate-200 transition-all">
                                Детали
                            </a>
                            
                            @if($assignment->status === 'proposed')
                                <form method="POST" action="{{ route('lk.executor.jobs.accept', $assignment) }}" class="flex-1">
                                    @csrf
                                    <button type="submit" class="w-full py-3 px-6 bg-emerald-600 text-white rounded-xl text-sm font-bold hover:bg-emerald-700 shadow-lg shadow-emerald-500/30 hover:-translate-y-0.5 transition-all">
                                        Принять
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('lk.executor.jobs.decline', $assignment) }}" class="flex-1">
                                    @csrf
                                    <button type="submit" class="w-full py-3 px-6 bg-white border-2 border-red-100 text-red-600 rounded-xl text-sm font-bold hover:bg-red-50 hover:border-red-200 transition-all">
                                        Отклонить
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($assignments->hasPages())
            <div class="mt-8 border-t border-slate-100 pt-8">
                {{ $assignments->links() }}
            </div>
        @endif
    @endif
</div>
@endsection