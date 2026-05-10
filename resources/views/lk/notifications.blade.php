@extends('lk.layout')

@section('title', 'Уведомления')

@section('content')
<div class="space-y-10" data-scroll-container>
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-50 border border-amber-100 text-xs font-bold uppercase tracking-widest text-amber-600 mb-3">
                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                Уведомления
            </div>
            <h1 class="text-4xl font-black text-slate-900 tracking-tight">Входящие</h1>
            <p class="text-slate-500 font-medium mt-2">Оставайтесь в курсе всех событий</p>
        </div>
        {{-- Search (U22: add search functionality) --}}
        <div class="w-full md:w-80">
            <div class="relative">
                <input type="text" 
                       id="notification-search"
                       x-data="{ searchQuery: '', filterNotifications() { const query = this.searchQuery.toLowerCase(); document.querySelectorAll('.notification-item').forEach(item => { const text = item.textContent.toLowerCase(); item.style.display = text.includes(query) ? 'block' : 'none'; }); } }"
                       x-model="searchQuery"
                       @input="filterNotifications()"
                       placeholder="Поиск уведомлений..."
                       class="w-full px-4 py-3 pl-12 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500 transition-all"
                       aria-label="Поиск уведомлений">
                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
        </div>
    </div>

    {{-- Mark All As Read + Bulk Actions --}}
    @if($unreadNotifications->isNotEmpty())
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-[2rem] shadow-sm border border-blue-100 p-6 flex flex-col md:flex-row items-center justify-between gap-6" 
             x-data="{ loading: false, async markAllAsRead() { if (this.loading) return; this.loading = true; try { const response = await fetch('{{ route('lk.notifications.read-all') }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' } }); const data = await response.json(); if (data.success) { window.location.reload(); } } catch (e) { console.error(e); alert('Ошибка'); } finally { this.loading = false; } } }">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center text-blue-600 shadow-sm border border-blue-100">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>
                </div>
                <div>
                    <h3 class="text-lg font-black text-slate-900">Новые события</h3>
                    <p class="text-sm font-medium text-slate-600">Отметьте все как прочитанные, чтобы очистить ленту</p>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                <label class="flex items-center gap-2 px-4 py-3 bg-white border border-blue-200 rounded-xl cursor-pointer hover:bg-blue-50 transition-colors">
                    <input type="checkbox" x-model="selectAll" @change="if(selectAll) { selectedNotifications = [{{ $unreadNotifications->pluck('id')->join(',') }}]; } else { selectedNotifications = []; }" class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500">
                    <span class="text-sm font-bold text-blue-700">Выбрать все</span>
                </label>
                <button @click="markAllAsRead()" :disabled="loading" class="px-8 py-3 bg-blue-600 text-white rounded-xl font-bold hover:bg-blue-700 hover:shadow-lg hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0 transition-all text-sm flex items-center justify-center gap-2">
                    <span x-show="!loading">✓ Отметить все</span>
                    <span x-show="loading" class="flex items-center gap-2">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Обработка...
                    </span>
                </button>
            </div>
        </div>
    @endif

    {{-- Непрочитанные --}}
    <div class="bg-white rounded-[2.5rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 p-8">
        <h2 class="text-xl font-black text-slate-900 flex items-center gap-3 mb-8">
            <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center text-red-600">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
            </div>
            Новое
        </h2>
        
        @if($unreadNotifications->isEmpty())
            <div class="text-center py-16 bg-slate-50/50 rounded-3xl border border-dashed border-slate-200">
                <div class="w-20 h-20 bg-white rounded-full shadow-sm flex items-center justify-center mx-auto mb-4 text-slate-300">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
                </div>
                <p class="text-slate-400 font-bold">У вас нет непрочитанных уведомлений</p>
            </div>
        @else
            <div x-data="{ 
    selectedNotifications: [],
    searchQuery: '',
    selectAll: false,
    async bulkDelete() {
        if (this.selectedNotifications.length === 0) return;
        if (!confirm('Удалить выбранные уведомления?')) return;
        try {
            const response = await fetch('{{ route('lk.notifications.bulk-delete') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ ids: this.selectedNotifications })
            });
            if (response.ok) {
                window.location.reload();
            } else {
                alert('Ошибка удаления');
            }
        } catch (e) {
            alert('Ошибка соединения');
        }
    }
}" class="space-y-4">
                @foreach($unreadNotifications as $notification)
                    @php $type = data_get($notification->data, 'type'); @endphp
                    <div x-data="{ hidden: false, loading: false }" x-show="!hidden" class="notification-item group relative overflow-hidden rounded-2xl bg-white border border-blue-100 shadow-sm p-5 hover:shadow-md hover:border-blue-200 transition-all">
                        <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-blue-500"></div>
                        
                        <div class="flex items-start justify-between gap-4 pl-3">
                            <div class="flex items-start gap-3">
                                <input type="checkbox" x-model="selectedNotifications" value="{{ $notification->id }}" class="w-5 h-5 rounded border-2 border-slate-300 text-amber-500 focus:ring-amber-500 focus:ring-offset-2 cursor-pointer mt-1" aria-label="Выбрать уведомление">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <h3 class="font-bold text-slate-900 text-lg">
                                            @if($type === 'payout.requested')
                                                💰 Запрошена выплата
                                            @elseif($type === 'payout.status_changed')
                                                📋 Статус выплаты изменён
                                            @elseif(str_contains($type ?? '', 'roadside'))
                                                🚗 Дорожная помощь
                                            @else
                                                🔔 {{ $notification->data['title'] ?? 'Уведомление' }}
                                            @endif
                                        </h3>
                                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                    </div>
                                    
                                    <div class="text-slate-600 font-medium mb-3 leading-relaxed">
                                        @if($type === 'payout.requested')
                                            Запрошена сумма: <span class="text-slate-900 font-bold">{{ number_format(data_get($notification->data, 'amount', 0), 0) }} {{ data_get($notification->data, 'currency', 'NOK') }}</span>
                                        @elseif($type === 'payout.status_changed')
                                            Статус изменен с <span class="line-through text-slate-400">{{ data_get($notification->data, 'old_status', '—') }}</span> на <span class="px-2 py-0.5 bg-blue-50 text-blue-700 rounded-md text-xs font-bold uppercase">{{ data_get($notification->data, 'new_status', '—') }}</span>
                                        @elseif(str_contains($type ?? '', 'roadside'))
                                            {{ data_get($notification->data, 'location', data_get($notification->data, 'message', 'Адрес не указан')) }}
                                        @else
                                            {{ $notification->data['body'] ?? $notification->data['message'] ?? 'Нет описания' }}
                                        @endif
                                    </div>
                                    
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">{{ $notification->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                            
                            <button @click="
                                if (loading) return;
                                loading = true;
                                fetch('{{ route('lk.notifications.read', $notification->id) }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    },
                                })
                                .then(res => res.json())
                                .then(data => { if (data.success) { hidden = true; } })
                                .catch(err => console.error(err))
                                .finally(() => { loading = false; });
                            " :disabled="loading" class="flex-shrink-0 w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center hover:bg-blue-600 hover:text-white transition-all duration-300">
                                <svg x-show="!loading" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                <svg x-show="loading" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- История --}}
    <div class="bg-white rounded-[2.5rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 p-8">
        <div class="flex items-center justify-between mb-8">
            <h2 class="text-xl font-black text-slate-900 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                История
            </h2>
            <span class="text-sm font-medium text-slate-400">{{ $readNotifications->count() }} записей</span>
        </div>

        @if($readNotifications->isEmpty())
            <div class="text-center py-12 text-slate-400 font-medium">История пуста</div>
        @else
            <div class="space-y-4 opacity-75 hover:opacity-100 transition-opacity notification-item">
                @foreach($readNotifications as $notification)
                    @php $type = data_get($notification->data, 'type'); @endphp
                    <div class="p-4 rounded-2xl bg-white border border-slate-100 hover:border-slate-200 transition-all">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-bold text-slate-700">
                                    @if($type === 'payout.requested')
                                        💰 Выплата {{ number_format(data_get($notification->data, 'amount', 0), 0) }} {{ data_get($notification->data, 'currency', 'NOK') }}
                                    @elseif($type === 'payout.status_changed')
                                        📋 Статус: {{ data_get($notification->data, 'new_status', '—') }}
                                    @else
                                        {{ $notification->data['title'] ?? 'Уведомление' }}
                                    @endif
                                </h4>
                                <p class="text-xs text-slate-400 font-bold mt-1 uppercase tracking-wider">{{ $notification->created_at->format('d.m.Y H:i') }}</p>
                            </div>
                            <div class="text-slate-300">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection