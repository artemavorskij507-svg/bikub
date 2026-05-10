@extends('lk.layout')

@section('title', 'Debug: состояние Worker LK')

@section('content')
<div class="space-y-8" data-scroll-container>
    {{-- Header --}}
    <div class="bg-white/80 backdrop-blur-xl rounded-[2rem] shadow-lg border border-white/50 p-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-100 text-xs font-bold uppercase tracking-widest text-slate-600 mb-2">
                <span class="w-2 h-2 rounded-full bg-slate-500 animate-pulse"></span>
                Developer Mode
            </div>
            <h1 class="text-2xl font-black text-slate-900">Debug: состояние Worker LK</h1>
            <p class="text-sm text-slate-500 font-medium">Эта страница только для локальной отладки. Показывает, что привязано к текущему пользователю.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {{-- Пользователь --}}
        <div class="bg-white/80 backdrop-blur-xl rounded-[2rem] shadow-lg border border-white/50 p-6 space-y-4">
            <h2 class="text-lg font-black text-slate-900 border-b border-slate-100 pb-2">Пользователь</h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                <div>
                    <dt class="text-xs font-bold text-slate-400 uppercase tracking-wider">ID</dt>
                    <dd class="font-mono font-medium text-slate-900">{{ $user->id }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-slate-400 uppercase tracking-wider">Имя</dt>
                    <dd class="font-medium text-slate-900">{{ $user->name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-slate-400 uppercase tracking-wider">Email</dt>
                    <dd class="font-medium text-slate-900">{{ $user->email }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-slate-400 uppercase tracking-wider">Роли</dt>
                    <dd class="font-medium text-slate-900">
                        @if(method_exists($user, 'roles'))
                            {{ $user->roles->pluck('name')->join(', ') ?: '—' }}
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-slate-400 uppercase tracking-wider">Онлайн-статус</dt>
                    <dd class="mt-1">
                        @if($workerStatus)
                            @if($workerStatus->is_online)
                                <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-bold bg-green-100 text-green-700">
                                    🟢 Онлайн
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-600">
                                    ⚪ Оффлайн
                                </span>
                            @endif
                        @else
                            <span class="text-xs text-slate-400 italic">нет записи worker_status</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-bold text-slate-400 uppercase tracking-wider">Непрочитанные уведомления</dt>
                    <dd class="font-medium text-slate-900">{{ $unreadNotificationsCount }}</dd>
                </div>
            </dl>
        </div>

        {{-- Активные заказы --}}
        <div class="bg-white/80 backdrop-blur-xl rounded-[2rem] shadow-lg border border-white/50 p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                <h2 class="text-lg font-black text-slate-900">Активные заказы ({{ $activeOrders->count() }})</h2>
                <a href="{{ route('lk.orders.index') }}" class="text-xs font-bold text-blue-600 hover:text-blue-700 bg-blue-50 px-2 py-1 rounded-lg transition">Все заказы →</a>
            </div>

            @if($activeOrders->isEmpty())
                <div class="text-center py-8">
                    <p class="text-slate-400 font-medium">Нет активных заказов</p>
                </div>
            @else
                <ul class="divide-y divide-slate-100 text-sm">
                    @foreach($activeOrders as $order)
                        <li class="py-3 flex items-center justify-between group hover:bg-slate-50 rounded-xl px-2 transition-colors">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-slate-900">#{{ $order->id }}</span>
                                    <span class="text-xs bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded">{{ $order->order_number ?? '—' }}</span>
                                </div>
                                <span class="text-xs font-medium text-slate-400">
                                    {{ $order->created_at?->format('d.m H:i') }}
                                </span>
                            </div>
                            <a href="{{ route('lk.orders.show', $order) }}" class="text-xs font-bold text-blue-600 hover:text-blue-700 bg-white border border-slate-200 px-3 py-1.5 rounded-lg shadow-sm hover:shadow transition">Открыть</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Завершённые заказы --}}
        <div class="bg-white/80 backdrop-blur-xl rounded-[2rem] shadow-lg border border-white/50 p-6 space-y-4">
            <h2 class="text-lg font-black text-slate-900 border-b border-slate-100 pb-2">Завершённые заказы (10)</h2>
            @if($completedOrders->isEmpty())
                <div class="text-center py-8">
                    <p class="text-slate-400 font-medium">История пуста</p>
                </div>
            @else
                <ul class="divide-y divide-slate-100 text-sm">
                    @foreach($completedOrders as $order)
                        <li class="py-3 flex items-center justify-between hover:bg-slate-50 rounded-xl px-2 transition-colors">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-slate-900">#{{ $order->id }}</span>
                                    <span class="text-xs bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded">{{ $order->order_number ?? '—' }}</span>
                                </div>
                                <span class="text-xs font-medium text-slate-400">
                                    {{ $order->completed_at?->format('d.m H:i') ?? $order->updated_at?->format('d.m H:i') }}
                                </span>
                            </div>
                            <a href="{{ route('lk.orders.show', $order) }}" class="text-xs font-bold text-slate-500 hover:text-slate-700">Открыть</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Выплаты --}}
        <div class="bg-white/80 backdrop-blur-xl rounded-[2rem] shadow-lg border border-white/50 p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                <h2 class="text-lg font-black text-slate-900">Выплаты (10)</h2>
                <a href="{{ route('lk.wallet') }}" class="text-xs font-bold text-amber-600 hover:text-amber-700 bg-amber-50 px-2 py-1 rounded-lg transition">Кошелёк →</a>
            </div>

            @if($payouts->isEmpty())
                <div class="text-center py-8">
                    <p class="text-slate-400 font-medium">Выплат пока нет</p>
                </div>
            @else
                <ul class="divide-y divide-slate-100 text-sm">
                    @foreach($payouts as $payout)
                        <li class="py-3 flex items-center justify-between hover:bg-slate-50 rounded-xl px-2 transition-colors">
                            <div>
                                <span class="font-bold text-slate-900 block">
                                    {{ number_format($payout->amount, 2, ',', ' ') }} {{ $payout->currency ?? 'NOK' }}
                                </span>
                                <span class="text-xs font-medium text-slate-400">
                                    {{ $payout->status }} · {{ $payout->created_at?->format('d.m H:i') }}
                                </span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Тикеты поддержки --}}
        <div class="bg-white/80 backdrop-blur-xl rounded-[2rem] shadow-lg border border-white/50 p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                <h2 class="text-lg font-black text-slate-900">Тикеты (10)</h2>
                <a href="{{ route('lk.support') }}" class="text-xs font-bold text-sky-600 hover:text-sky-700 bg-sky-50 px-2 py-1 rounded-lg transition">Поддержка →</a>
            </div>

            @if($tickets->isEmpty())
                <div class="text-center py-8">
                    <p class="text-slate-400 font-medium">Тикетов нет</p>
                </div>
            @else
                <ul class="divide-y divide-slate-100 text-sm">
                    @foreach($tickets as $ticket)
                        <li class="py-3 hover:bg-slate-50 rounded-xl px-2 transition-colors">
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="font-bold text-slate-900">#{{ $ticket->number ?? $ticket->id }}</span>
                                    <span class="ml-1 text-slate-600">{{ $ticket->subject }}</span>
                                </div>
                                <span class="text-xs font-medium text-slate-400">
                                    {{ $ticket->status }}
                                </span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Смены --}}
        <div class="bg-white/80 backdrop-blur-xl rounded-[2rem] shadow-lg border border-white/50 p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                <h2 class="text-lg font-black text-slate-900">Предстоящие смены ({{ $upcomingShifts->count() }})</h2>
                <a href="{{ route('lk.schedule') }}" class="text-xs font-bold text-emerald-600 hover:text-emerald-700 bg-emerald-50 px-2 py-1 rounded-lg transition">График →</a>
            </div>

            @if($upcomingShifts->isEmpty())
                <div class="text-center py-8">
                    <p class="text-slate-400 font-medium">Смен не найдено</p>
                </div>
            @else
                <ul class="divide-y divide-slate-100 text-sm">
                    @foreach($upcomingShifts as $shift)
                        <li class="py-3 flex items-center justify-between hover:bg-slate-50 rounded-xl px-2 transition-colors">
                            <div>
                                <span class="font-bold text-slate-900 block">
                                    {{ $shift->start_at?->format('d.m H:i') }} — {{ $shift->end_at?->format('H:i') }}
                                </span>
                                @if($shift->zone)
                                    <span class="text-xs font-medium text-slate-500">{{ $shift->zone->name }}</span>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>
@endsection