@extends('lk.layout')

@section('title', 'Тикет #' . $ticket->number)

@section('content')
<div class="space-y-8" data-scroll-container>
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-50 border border-amber-100 text-xs font-bold uppercase tracking-widest text-amber-600 mb-3">
                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                Support Ticket
            </div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tight">{{ $ticket->subject }}</h1>
            <p class="text-slate-500 font-medium mt-2 flex items-center gap-2">
                <span>#{{ $ticket->number }}</span>
                <span class="text-slate-300">•</span>
                <span>{{ $ticket->category ? ucfirst(str_replace('_', ' ', $ticket->category)) : 'Общий вопрос' }}</span>
            </p>
        </div>
        
        <div class="flex items-center gap-3">
            @php
                $statusColors = [
                    'open' => 'bg-amber-100 text-amber-700 border-amber-200',
                    'in_progress' => 'bg-sky-100 text-sky-700 border-sky-200',
                    'resolved' => 'bg-green-100 text-green-700 border-green-200',
                    'closed' => 'bg-slate-100 text-slate-700 border-slate-200',
                ];
                $statusLabels = [
                    'open' => '🟠 Открыт', 'in_progress' => '🔵 В работе',
                    'resolved' => '✅ Решен', 'closed' => '⚫ Закрыт',
                ];
            @endphp
            <span class="px-4 py-2 rounded-xl text-sm font-black uppercase tracking-wider border {{ $statusColors[$ticket->status] ?? 'bg-slate-100 text-slate-600' }}">
                {{ $statusLabels[$ticket->status] ?? ucfirst($ticket->status) }}
            </span>
            <a href="{{ route('lk.support') }}" class="group flex items-center justify-center w-10 h-10 bg-white/80 backdrop-blur-sm border border-slate-200 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-white hover:shadow-lg transition-all">
                <svg class="w-5 h-5 group-hover:-translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Chat (Left Column) --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white/80 backdrop-blur-xl rounded-[2rem] shadow-lg border border-white/50 p-6 flex flex-col h-[600px]">
                <div class="flex-1 overflow-y-auto space-y-6 pr-2 custom-scrollbar">
                    {{-- Original Ticket Description --}}
                    <div class="flex justify-end">
                        <div class="max-w-[85%]">
                            <div class="flex items-center justify-end gap-2 mb-2">
                                <span class="text-xs font-bold text-slate-400">{{ $ticket->created_at->format('H:i') }}</span>
                                <span class="text-xs font-bold text-slate-600">Вы</span>
                            </div>
                            <div class="bg-sky-50 text-slate-800 rounded-2xl rounded-tr-none px-5 py-4 shadow-sm border border-sky-100">
                                <p class="text-sm whitespace-pre-line leading-relaxed">{{ $ticket->description }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Messages --}}
                    @foreach($messages as $msg)
                        @php $isWorker = $msg->sender_type === 'worker'; @endphp
                        <div class="flex {{ $isWorker ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[85%]">
                                <div class="flex items-center gap-2 mb-2 {{ $isWorker ? 'justify-end' : 'justify-start' }}">
                                    @if(!$isWorker) 
                                        <span class="text-xs font-bold text-blue-600">Поддержка</span>
                                        <span class="text-xs font-bold text-slate-400">{{ $msg->created_at->format('H:i') }}</span>
                                    @else
                                        <span class="text-xs font-bold text-slate-400">{{ $msg->created_at->format('H:i') }}</span>
                                        <span class="text-xs font-bold text-slate-600">Вы</span>
                                    @endif
                                </div>
                                <div class="rounded-2xl px-5 py-4 shadow-sm {{ $isWorker ? 'bg-sky-50 text-slate-800 border border-sky-100 rounded-tr-none' : 'bg-white text-slate-800 border border-slate-200 rounded-tl-none' }}">
                                    <p class="text-sm whitespace-pre-line leading-relaxed">{{ $msg->message }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    
                    @if($messages->isEmpty())
                        <div class="flex items-center justify-center h-32">
                            <p class="text-sm text-slate-400 font-medium bg-slate-50 px-4 py-2 rounded-full">Ожидайте ответа оператора...</p>
                        </div>
                    @endif
                </div>

                {{-- Reply Form --}}
                @if($ticket->status !== 'closed')
                    <div class="mt-6 pt-6 border-t border-slate-100">
                         @if(session('status'))
                            <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-xl text-xs font-bold text-green-700 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                {{ session('status') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('lk.support.tickets.messages.store', $ticket) }}" class="relative">
                            @csrf
                            <textarea 
                                name="message" 
                                rows="3" 
                                class="w-full pl-5 pr-14 py-4 rounded-2xl border-2 border-slate-200 bg-slate-50 focus:bg-white focus:border-sky-500 focus:ring-0 transition-all text-sm font-medium resize-none shadow-inner"
                                placeholder="Напишите сообщение..."
                                required
                            ></textarea>
                            <button type="submit" class="absolute right-3 bottom-3 p-2 bg-gradient-to-r from-sky-500 to-blue-600 text-white rounded-xl shadow-lg shadow-sky-200 hover:shadow-xl hover:scale-105 transition-all">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                            </button>
                        </form>
                    </div>
                @else
                    <div class="mt-6 pt-6 border-t border-slate-100 text-center">
                        <span class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 rounded-xl text-sm font-bold text-slate-500">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                            Тикет закрыт
                        </span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Meta Info (Right Column) --}}
        <div class="space-y-6">
            <div class="bg-white/80 backdrop-blur-xl rounded-[2rem] shadow-lg border border-white/50 p-6">
                <h3 class="text-lg font-black text-slate-900 mb-6">Детали</h3>
                
                <div class="space-y-6">
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Создан</p>
                        <p class="font-medium text-slate-900">{{ $ticket->created_at->format('d.m.Y H:i') }}</p>
                        <p class="text-xs text-slate-500 mt-1">{{ $ticket->created_at->diffForHumans() }}</p>
                    </div>
                    
                    <div>
                         <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Приоритет</p>
                         @php
                            $priorityEmoji = ['urgent' => '🔴', 'high' => '🟠', 'normal' => '🟢', 'low' => '🟡'];
                            $priorityLabel = ['urgent' => 'Срочный', 'high' => 'Высокий', 'normal' => 'Обычный', 'low' => 'Низкий'];
                        @endphp
                        <div class="flex items-center gap-2">
                            <span class="text-lg">{{ $priorityEmoji[$ticket->priority] ?? '⚪' }}</span>
                            <span class="font-bold text-slate-900">{{ $priorityLabel[$ticket->priority] ?? 'Обычный' }}</span>
                        </div>
                    </div>

                    @if($ticket->resolved_at)
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Решено</p>
                            <p class="font-medium text-slate-900">{{ $ticket->resolved_at->format('d.m.Y H:i') }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-[2rem] border border-amber-100 p-6">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl bg-white flex items-center justify-center text-amber-500 shadow-sm">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <h4 class="font-bold text-amber-900">Нужна помощь?</h4>
                </div>
                <p class="text-sm text-amber-800/80 mb-4">Если вопрос срочный, вы можете связаться с нами по телефону.</p>
                <a href="tel:+1234567890" class="flex items-center justify-center gap-2 w-full py-3 bg-white text-amber-600 rounded-xl font-bold text-sm shadow-sm hover:shadow-md transition-all">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                    Позвонить
                </a>
            </div>
        </div>
    </div>
</div>
@endsection