@extends('lk.layout')

@section('title', 'Кошелёк')

@section('content')
<div class="space-y-10" data-scroll-container>
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-50 border border-amber-100 text-xs font-bold uppercase tracking-widest text-amber-600 mb-3">
                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                Финансы
            </div>
            <h1 class="text-4xl font-black text-slate-900 tracking-tight">Кошелёк</h1>
            <p class="text-slate-500 font-medium mt-2">Ваш заработок, выплаты и баланс</p>
        </div>
        
        <div class="bg-white px-6 py-4 rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 flex items-center gap-4">
            <div class="w-12 h-12 bg-amber-100 rounded-2xl flex items-center justify-center text-amber-600">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <div>
                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-0.5">Баланс</div>
                <div class="text-2xl font-black text-slate-900">{{ number_format($availableForPayout ?? 0, 0) }} <span class="text-sm text-slate-400">kr</span></div>
            </div>
        </div>
    </div>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {{-- Total Earned --}}
        <div class="bg-white/80 backdrop-blur-xl rounded-[2rem] p-6 shadow-lg border border-white/50 hover:border-emerald-200 hover:shadow-[0_12px_40px_rgb(16,185,129,0.15)] transition-all duration-300 group hover:-translate-y-1">
            <div class="flex items-start justify-between mb-6">
                <div class="p-3 bg-gradient-to-br from-emerald-50 to-teal-50 rounded-2xl text-emerald-500 group-hover:scale-110 group-hover:rotate-6 transition-all duration-300 shadow-sm">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <span class="text-xs font-bold text-emerald-600 bg-emerald-100 px-2 py-1 rounded-lg">ВСЕГО</span>
            </div>
            <div class="text-3xl font-black text-slate-900 tracking-tight mb-1">{{ number_format($totalEarned ?? 0, 0) }} kr</div>
            <p class="text-sm font-semibold text-slate-400">Заработано</p>
        </div>

        {{-- Paid --}}
        <div class="bg-white/80 backdrop-blur-xl rounded-[2rem] p-6 shadow-lg border border-white/50 hover:border-blue-200 hover:shadow-[0_12px_40px_rgb(59,130,246,0.15)] transition-all duration-300 group hover:-translate-y-1">
            <div class="flex items-start justify-between mb-6">
                <div class="p-3 bg-gradient-to-br from-blue-50 to-sky-50 rounded-2xl text-blue-500 group-hover:scale-110 group-hover:rotate-6 transition-all duration-300 shadow-sm">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <span class="text-xs font-bold text-blue-600 bg-blue-100 px-2 py-1 rounded-lg">ВЫПЛАЧЕНО</span>
            </div>
            <div class="text-3xl font-black text-slate-900 tracking-tight mb-1">{{ number_format($totalPaid ?? 0, 0) }} kr</div>
            <p class="text-sm font-semibold text-slate-400">Получено</p>
        </div>

        {{-- Pending --}}
        <div class="bg-white/80 backdrop-blur-xl rounded-[2rem] p-6 shadow-lg border border-white/50 hover:border-amber-200 hover:shadow-[0_12px_40px_rgb(245,158,11,0.15)] transition-all duration-300 group hover:-translate-y-1">
            <div class="flex items-start justify-between mb-6">
                <div class="p-3 bg-gradient-to-br from-amber-50 to-orange-50 rounded-2xl text-amber-500 group-hover:scale-110 group-hover:rotate-6 transition-all duration-300 shadow-sm">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <span class="text-xs font-bold text-amber-600 bg-amber-100 px-2 py-1 rounded-lg">В ОБРАБОТКЕ</span>
            </div>
            <div class="text-3xl font-black text-slate-900 tracking-tight mb-1">{{ number_format($pendingPayouts ?? 0, 0) }} kr</div>
            <p class="text-sm font-semibold text-slate-400">Ожидает выплаты</p>
        </div>

        {{-- Available --}}
        <div class="bg-white/90 backdrop-blur-xl rounded-[2rem] p-6 shadow-lg border border-amber-100 relative overflow-hidden group hover:-translate-y-1 hover:shadow-[0_16px_50px_rgb(245,158,11,0.2)] hover:border-amber-200 transition-all duration-300">
            <div class="absolute top-0 right-0 w-32 h-32 bg-amber-200/40 rounded-full -mr-10 -mt-10 blur-xl group-hover:bg-amber-300/60 transition-colors"></div>
            <div class="absolute inset-0 bg-gradient-to-br from-amber-50/40 to-orange-50/20 opacity-70"></div>
            <div class="relative z-10">
                <div class="flex items-start justify-between mb-6">
                    <div class="p-3 bg-white rounded-2xl text-amber-500 shadow-sm">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    </div>
                    <span class="text-xs font-black text-amber-700 bg-amber-100 px-3 py-1 rounded-lg border border-amber-200">ДОСТУПНО</span>
                </div>
                <div class="text-3xl font-black tracking-tight mb-1 text-slate-900">{{ number_format($availableForPayout ?? 0, 0) }} kr</div>
                <p class="text-sm font-medium text-slate-600">Можно вывести</p>
            </div>
        </div>
    </div>

    {{-- Request Payout --}}
    @if($availableForPayout > 0)
        <div class="bg-white rounded-[2.5rem] shadow-xl border border-slate-100 overflow-hidden relative">
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-emerald-400 via-teal-500 to-cyan-500"></div>
            <div class="p-8 md:p-10">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-400 to-teal-600 flex items-center justify-center text-white shadow-lg shadow-emerald-500/30">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                    </div>
                    <div>
                        <h2 class="text-2xl font-black text-slate-900 tracking-tight">Запросить выплату</h2>
                        <p class="text-slate-500 font-medium">Создайте заявку на вывод средств</p>
                    </div>
                </div>

                <form x-data="{
                    amount: {{ $availableForPayout }},
                    method: 'bank',
                    note: '',
                    loading: false,
                    async submit() {
                        if (this.loading || this.amount <= 0 || this.amount > {{ $availableForPayout }}) return;
                        this.loading = true;
                        try {
                            const response = await fetch('{{ route('lk.wallet.request-payout') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({
                                    amount: this.amount,
                                    method: this.method,
                                    note: this.note,
                                }),
                            });
                            const data = await response.json();
                            if (data.success) {
                                if(window.showToast) window.showToast(data.message, 'success');
                                else alert(data.message);
                                setTimeout(() => window.location.reload(), 1000);
                            } else {
                                if(window.showToast) window.showToast(data.message || 'Ошибка', 'error');
                                else alert(data.message);
                            }
                        } catch (e) {
                            console.error(e);
                            if(window.showToast) window.showToast('Ошибка сети', 'error');
                        } finally {
                            this.loading = false;
                        }
                    }
                }" @submit.prevent="submit()" class="space-y-8">
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        {{-- Amount --}}
                        <div class="space-y-3">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Сумма (kr)</label>
                            <div class="relative group">
                                <input type="number" step="0.01" min="0.01" :max="{{ $availableForPayout }}" x-model="amount" 
                                    class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl px-5 py-4 text-lg font-bold text-slate-900 focus:outline-none focus:border-emerald-500 focus:ring-0 transition-colors"
                                    :disabled="loading" required>
                                <div class="absolute right-5 top-1/2 -translate-y-1/2 text-slate-400 font-bold pointer-events-none group-focus-within:text-emerald-500 transition-colors">kr</div>
                            </div>
                            <p class="text-xs font-medium text-slate-400">Максимум: <span class="text-emerald-600 font-bold">{{ number_format($availableForPayout, 2, ',', ' ') }} kr</span></p>
                        </div>

                        {{-- Method --}}
                        <div class="space-y-3">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Способ выплаты</label>
                            <div class="relative">
                                <select x-model="method" 
                                    class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl px-5 py-4 text-lg font-bold text-slate-900 focus:outline-none focus:border-emerald-500 focus:ring-0 transition-colors appearance-none cursor-pointer"
                                    :disabled="loading">
                                    <option value="bank">🏦 Банковский перевод</option>
                                    <option value="vipps">📱 Vipps</option>
                                    <option value="cash">💸 Наличные</option>
                                </select>
                                <div class="absolute right-5 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                                </div>
                            </div>
                        </div>

                        {{-- Note --}}
                        <div class="space-y-3">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Примечание</label>
                            <input type="text" x-model="note" 
                                class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl px-5 py-4 text-lg font-medium text-slate-900 focus:outline-none focus:border-emerald-500 focus:ring-0 transition-colors placeholder:text-slate-300"
                                :disabled="loading" placeholder="Доп. информация...">
                        </div>
                    </div>

                    <div class="flex justify-end pt-4">
                        <button type="submit" :disabled="loading || amount <= 0 || amount > {{ $availableForPayout }}"
                            class="relative overflow-hidden inline-flex items-center gap-3 px-10 py-4 bg-slate-900 text-white rounded-2xl font-bold text-lg hover:bg-black hover:shadow-xl hover:-translate-y-1 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0 disabled:hover:shadow-none transition-all duration-300 group">
                            <span class="relative z-10" x-text="loading ? 'Отправка...' : 'Отправить запрос'"></span>
                            <svg x-show="!loading" class="w-5 h-5 relative z-10 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" /></svg>
                            <svg x-show="loading" class="w-5 h-5 animate-spin relative z-10" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {{-- Recent Orders --}}
        <div class="bg-white rounded-[2.5rem] shadow-xl border border-slate-100 p-8 h-full flex flex-col">
            <h2 class="text-xl font-black text-slate-900 flex items-center gap-3 mb-8">
                <div class="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center text-green-600">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                Последние заказы
            </h2>

            <div class="flex-1 overflow-y-auto pr-2 custom-scrollbar space-y-4 max-h-[400px]">
                @forelse($recentOrders as $item)
                    @php $order = $item['order']; $payoutAmount = $item['payout_amount']; @endphp
                    <a href="{{ route('lk.orders.show', $order) }}" class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl hover:bg-white hover:shadow-lg hover:border-green-100 hover:ring-2 hover:ring-green-50 transition-all border border-transparent group">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center text-slate-400 font-bold text-xs shadow-sm border border-slate-100 group-hover:border-green-200 group-hover:text-green-600 transition-colors">
                                {{ substr($order->order_number ?? '#', -2) }}
                            </div>
                            <div>
                                <div class="font-bold text-slate-900 group-hover:text-green-700 transition-colors">{{ $order->order_number ?? 'Заказ #'.$order->id }}</div>
                                <div class="text-xs text-slate-500 font-medium">{{ $order->completed_at?->format('d.m.Y H:i') ?? 'N/A' }}</div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-black text-green-600">+{{ number_format($payoutAmount, 0) }} kr</div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider group-hover:text-green-500 transition-colors">Зачислено</div>
                        </div>
                    </a>
                @empty
                    <div class="text-center py-12">
                        <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-300">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
                        </div>
                        <p class="text-slate-400 font-medium">Нет завершенных заказов</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Payout History --}}
        <div class="bg-white rounded-[2.5rem] shadow-xl border border-slate-100 p-8 h-full flex flex-col">
            <h2 class="text-xl font-black text-slate-900 flex items-center gap-3 mb-8">
                <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                </div>
                История выплат
            </h2>

            <div class="flex-1 overflow-y-auto pr-2 custom-scrollbar space-y-4 max-h-[400px]">
                @forelse($payouts as $payout)
                    @php
                        $statusStyles = [
                            'pending' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'icon' => '⏳'],
                            'processing' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'icon' => '⚙️'],
                            'paid' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700', 'icon' => '✅'],
                            'completed' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700', 'icon' => '✅'],
                            'rejected' => ['bg' => 'bg-red-100', 'text' => 'text-red-700', 'icon' => '❌'],
                            'cancelled' => ['bg' => 'bg-slate-100', 'text' => 'text-slate-600', 'icon' => '🚫'],
                        ];
                        $status = $statusStyles[$payout->status] ?? ['bg' => 'bg-slate-100', 'text' => 'text-slate-600', 'icon' => '❓'];
                        
                        $methodLabels = ['bank' => 'Банк', 'vipps' => 'Vipps', 'cash' => 'Наличные'];
                    @endphp
                    <div class="p-5 bg-white border border-slate-100 rounded-2xl shadow-sm hover:shadow-md transition-all group">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <span class="text-2xl">{{ $status['icon'] }}</span>
                                <div>
                                    <div class="text-lg font-black text-slate-900">{{ number_format($payout->amount, 0) }} kr</div>
                                    <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">{{ $methodLabels[$payout->method] ?? 'Неизвестно' }}</div>
                                </div>
                            </div>
                            <span class="px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wider {{ $status['bg'] }} {{ $status['text'] }}">
                                {{ $payout->status }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between pt-3 border-t border-slate-50">
                            <span class="text-xs font-medium text-slate-400">{{ $payout->created_at->format('d.m.Y H:i') }}</span>
                            @if($payout->note)
                                <span class="text-xs text-slate-500 italic max-w-[150px] truncate" title="{{ $payout->note }}">{{ $payout->note }}</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-12">
                        <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-300">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <p class="text-slate-400 font-medium">История пуста</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>
@endsection