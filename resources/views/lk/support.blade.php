@extends('lk.layout')

@section('title', 'Поддержка')

@section('content')
<div class="space-y-10" data-scroll-container>
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-50 border border-amber-100 text-xs font-bold uppercase tracking-widest text-amber-600 mb-3">
                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                Помощь
            </div>
            <h1 class="text-4xl font-black text-slate-900 tracking-tight">Поддержка</h1>
            <p class="text-slate-500 font-medium mt-2">Мы всегда готовы помочь вам. Напишите нам — и мы решим вашу проблему!</p>
        </div>
    </div>

    {{-- Create Ticket Form --}}
    <div class="bg-white/80 backdrop-blur-xl rounded-[2.5rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 overflow-hidden relative">
        <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-amber-400 via-orange-500 to-amber-600"></div>
        <div class="p-8 md:p-10">
            <h2 class="text-2xl font-black text-slate-900 mb-8 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </div>
                Новый запрос
            </h2>

            @if (session('status'))
                <div class="mb-8 p-4 bg-green-50 border border-green-200 rounded-2xl flex items-center gap-3 text-green-800 font-bold shadow-sm">
                    <div class="w-8 h-8 rounded-full bg-green-200 flex items-center justify-center flex-shrink-0">✓</div>
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-8 p-4 bg-red-50 border border-red-200 rounded-2xl text-red-800 shadow-sm">
                    <div class="flex items-center gap-2 font-black mb-2">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Ошибка в форме
                    </div>
                    <ul class="list-disc list-inside space-y-1 text-sm font-medium ml-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('lk.support.tickets.store') }}" class="space-y-8">
                @csrf

                <div class="space-y-3">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Тема обращения</label>
                    <input type="text" name="subject" value="{{ old('subject') }}"
                        class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl px-5 py-4 text-base font-bold text-slate-900 focus:outline-none focus:border-amber-500 focus:ring-0 transition-colors placeholder:text-slate-300"
                        placeholder="Например: Проблема с оплатой по заказу #1234" required>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-3">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Приоритет</label>
                        <div class="relative">
                            <select name="priority" class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl px-5 py-4 text-base font-bold text-slate-900 focus:outline-none focus:border-amber-500 focus:ring-0 transition-colors appearance-none cursor-pointer">
                                <option value="normal" @selected(old('priority', 'normal') === 'normal')>🟢 Обычный</option>
                                <option value="high" @selected(old('priority') === 'high')>🟠 Высокий</option>
                                <option value="urgent" @selected(old('priority') === 'urgent')>🔴 Срочный</option>
                                <option value="low" @selected(old('priority') === 'low')>🟡 Низкий</option>
                            </select>
                            <div class="absolute right-5 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Категория</label>
                        <div class="relative">
                            <select name="category" class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl px-5 py-4 text-base font-bold text-slate-900 focus:outline-none focus:border-amber-500 focus:ring-0 transition-colors appearance-none cursor-pointer">
                                <option value="">Выберите категорию...</option>
                                <option value="orders">📦 Заказы</option>
                                <option value="payments">💰 Платежи и выплаты</option>
                                <option value="shifts">📅 Смены и график</option>
                                <option value="account">👤 Личный кабинет</option>
                                <option value="other">❓ Другое</option>
                            </select>
                            <div class="absolute right-5 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-3">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Описание проблемы</label>
                    <textarea name="message" rows="5"
                        class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl px-5 py-4 text-base font-medium text-slate-900 focus:outline-none focus:border-amber-500 focus:ring-0 transition-colors placeholder:text-slate-300 resize-none"
                        placeholder="Опишите проблему максимально подробно..."
                        required>{{ old('message') }}</textarea>
                </div>

                <div class="flex flex-col md:flex-row items-center justify-between gap-6 pt-4 border-t border-slate-100">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Отвечаем в течение 2 часов</p>
                    <button type="submit" class="w-full md:w-auto inline-flex items-center justify-center gap-3 px-10 py-4 bg-slate-900 text-white rounded-2xl font-bold text-lg hover:bg-black hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group">
                        <span>Отправить запрос</span>
                        <svg class="w-5 h-5 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {{-- Active Tickets --}}
        <div class="bg-white rounded-[2.5rem] p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 h-full">
            <h2 class="text-xl font-black text-slate-900 mb-8 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                В работе ({{ $activeTickets->count() }})
            </h2>

            @if($activeTickets->isEmpty())
                <div class="text-center py-12 text-slate-400 font-medium">Нет активных запросов</div>
            @else
                <div class="space-y-4">
                    @foreach($activeTickets as $ticket)
                        <a href="{{ route('lk.support.tickets.show', $ticket) }}"
                           class="block p-5 bg-white border border-slate-100 rounded-3xl shadow-sm hover:shadow-md hover:border-amber-200 transition-all group">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="text-xs font-black text-slate-400">#{{ $ticket->id }}</span>
                                        @php
                                            $priorityIcons = ['urgent' => '🔴', 'high' => '🟠', 'normal' => '🟢', 'low' => '🟡'];
                                        @endphp
                                        <span class="text-xs">{{ $priorityIcons[$ticket->priority] ?? '●' }}</span>
                                    </div>
                                    <div class="font-bold text-slate-900 text-lg group-hover:text-amber-600 transition-colors line-clamp-1">{{ $ticket->subject }}</div>
                                    <p class="text-xs font-bold text-slate-400 mt-2 uppercase tracking-wider">{{ $ticket->created_at->diffForHumans() }}</p>
                                </div>
                                @php
                                    $statusConfig = ['open' => ['bg'=>'bg-amber-100', 'text'=>'text-amber-700', 'label'=>'Открыт'], 'in_progress' => ['bg'=>'bg-sky-100', 'text'=>'text-sky-700', 'label'=>'В работе'], 'resolved' => ['bg'=>'bg-green-100', 'text'=>'text-green-700', 'label'=>'Решен']];
                                    $st = $statusConfig[$ticket->status] ?? ['bg'=>'bg-slate-100', 'text'=>'text-slate-600', 'label'=>ucfirst($ticket->status)];
                                @endphp
                                <span class="px-3 py-1.5 rounded-lg text-xs font-black uppercase tracking-wider {{ $st['bg'] }} {{ $st['text'] }}">
                                    {{ $st['label'] }}
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Resolved Tickets --}}
        <div class="bg-white rounded-[2.5rem] p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 h-full">
            <h2 class="text-xl font-black text-slate-900 mb-8 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center text-green-600">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                </div>
                Решено ({{ $resolvedTickets->count() }})
            </h2>

            @if($resolvedTickets->isEmpty())
                <div class="text-center py-12 text-slate-400 font-medium">История пуста</div>
            @else
                <div class="space-y-4 opacity-75 hover:opacity-100 transition-opacity">
                    @foreach($resolvedTickets as $ticket)
                        <div class="p-5 bg-slate-50 border border-slate-100 rounded-3xl flex items-center justify-between">
                            <div>
                                <div class="font-bold text-slate-700">#{{ $ticket->id }} — {{ $ticket->subject }}</div>
                                <p class="text-xs font-bold text-slate-400 mt-1 uppercase tracking-wider">Решён {{ $ticket->resolved_at?->format('d.m.Y') ?? 'N/A' }}</p>
                            </div>
                            <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center text-green-600">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- FAQ --}}
    <div class="bg-white rounded-[2.5rem] p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100">
        <h2 class="text-xl font-black text-slate-900 mb-8 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            Частые вопросы
        </h2>

        <div class="space-y-4">
            @forelse($faqItems as $item)
                <details class="group bg-slate-50 rounded-2xl overflow-hidden transition-all duration-300 open:bg-white open:shadow-lg open:ring-1 open:ring-slate-100">
                    <summary class="flex items-center justify-between cursor-pointer p-5 font-bold text-slate-800 hover:text-amber-600 transition-colors list-none">
                        <span>{{ $item['question'] }}</span>
                        <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center text-slate-400 shadow-sm group-open:rotate-180 transition-transform duration-300 group-open:bg-amber-50 group-open:text-amber-600">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </summary>
                    <div class="px-5 pb-5 text-sm font-medium text-slate-600 leading-relaxed border-t border-slate-100 pt-4">
                        {{ $item['answer'] }}
                    </div>
                </details>
            @empty
                <p class="text-slate-400 text-center font-medium py-8">FAQ будут добавлены позже</p>
            @endforelse
        </div>
    </div>
</div>
@endsection