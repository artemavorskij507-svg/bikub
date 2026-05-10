@extends('lk.layout')

@section('title', 'Главная')

@section('content')
<div x-data="workerDashboard()" x-init="init()" class="space-y-6">
    <x-bikube.os-shell>
        <x-bikube.page-header
            eyebrow="BiKuBe OS / LK"
            :title="'Здравствуйте, '.(explode(' ', $user->name)[0] ?? 'коллега')"
            subtitle="Worker cockpit for assignments, shifts and task flow."
            badge="Wave 3B UI Core v2"
            :refresh-url="route('lk.dashboard')"
            :open-url="route('lk.orders.index')"
            open-label="Открыть заказы"
            :chips="['Assigned orders', 'Available jobs', 'Live shift status']"
        >
            <x-slot:actions>
                <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-sm font-bold" :class="isOnline ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700'">
                    <span class="h-2.5 w-2.5 rounded-full" :class="isOnline ? 'bg-emerald-500' : 'bg-slate-500'"></span>
                    <span x-text="isOnline ? 'Онлайн' : 'Офлайн'"></span>
                </span>
                <button type="button" @click="toggleStatus" class="bikube-os-btn" :class="isOnline ? 'bikube-os-btn-danger' : 'bikube-os-btn-primary'">
                    <span x-text="isOnline ? 'Завершить смену' : 'Выйти на линию'"></span>
                </button>
            </x-slot:actions>
        </x-bikube.page-header>

        <section class="bikube-os-grid-4">
            <x-bikube.kpi-card label="Доход сегодня" value="Live" hint="Обновляется автоматически" tone="emerald" />
            <x-bikube.kpi-card label="Выполнено сегодня" :value="$todayCompleted ?? 0" hint="Закрытые задачи" tone="blue" />
            <x-bikube.kpi-card label="Активное задание" :value="(($activeDeliveryOrderData ?? null) || ($activeAssignmentData ?? null)) ? 'В работе' : 'Нет'" hint="Текущий рабочий контекст" tone="amber" />
            <x-bikube.kpi-card label="Следующая смена" :value="$nextShift ? \Carbon\Carbon::parse($nextShift->start_at)->translatedFormat('d M, H:i') : 'Не назначена'" hint="План смен" tone="slate" />
        </section>

        <section class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-2 space-y-6">
                <x-bikube.action-card title="Текущая задача" subtitle="Активный заказ или назначение">
                    @php
                        $activeOrderId = $activeDeliveryOrderData['order_id'] ?? $activeAssignmentData['order_id'] ?? null;
                    @endphp
                    @if($activeOrderId)
                        <div class="mb-3">
                            <a href="{{ route('lk.orders.show', $activeOrderId) }}" class="bikube-os-btn bikube-os-btn-primary">Открыть заказ</a>
                        </div>
                    @endif

                    @if($activeDeliveryOrderData || $activeAssignmentData)
                        @php
                            $activeNumber = $activeDeliveryOrderData['order_number'] ?? $activeAssignmentData['order_number'] ?? 'Без номера';
                            $activeAddress = $activeDeliveryOrderData['address'] ?? $activeAssignmentData['address'] ?? 'Адрес не указан';
                            $activeCity = $activeDeliveryOrderData['city'] ?? $activeAssignmentData['city'] ?? null;
                        @endphp
                        <div class="bikube-os-info">
                            <p class="bikube-os-info-label">Заказ</p>
                            <p class="bikube-os-info-value">{{ $activeNumber }}</p>
                            <p class="text-sm text-slate-700 mt-2">{{ $activeAddress }}</p>
                            @if($activeCity)
                                <p class="text-xs text-slate-500 mt-1">{{ $activeCity }}</p>
                            @endif
                        </div>
                    @else
                        <x-bikube.empty-state
                            title="Сейчас нет активных задач"
                            message="Новые назначения появятся автоматически."
                        />
                    @endif
                </x-bikube.action-card>

                <x-bikube.action-card title="Assigned orders" subtitle="Назначенные заказы исполнителя">
                    <div class="space-y-3">
                        @forelse(($assignedOrders ?? collect()) as $order)
                            @php
                                $meta = is_array($order->metadata ?? null) ? $order->metadata : [];
                                $eta = $order->scheduled_at
                                    ?? ($meta['eta_at'] ?? null)
                                    ?? ($meta['eta'] ?? null);
                                $etaFormatted = \Illuminate\Support\Carbon::make($eta)?->format('d.m H:i');
                                $clientLabel = $meta['client_name'] ?? $meta['guest_name'] ?? 'Client';
                            @endphp
                            <x-bikube.order-card
                                :title="$order->order_number ?? ('#'.$order->id)"
                                :meta="'ETA '.($etaFormatted ?? '—').' · Worker: you'"
                                :status="$order->status"
                                :payment="$order->payment_status"
                            >
                                <div class="bikube-os-info-grid">
                                    <div class="bikube-os-info">
                                        <p class="bikube-os-info-label">Pickup</p>
                                        <p class="bikube-os-info-value">{{ $meta['pickup_address'] ?? '—' }}</p>
                                    </div>
                                    <div class="bikube-os-info">
                                        <p class="bikube-os-info-label">Dropoff</p>
                                        <p class="bikube-os-info-value">{{ $meta['dropoff_address'] ?? '—' }}</p>
                                    </div>
                                    <div class="bikube-os-info">
                                        <p class="bikube-os-info-label">Client</p>
                                        <p class="bikube-os-info-value">{{ $clientLabel }}</p>
                                    </div>
                                </div>
                                <div class="mt-3 bikube-os-actions">
                                    <a href="{{ route('lk.orders.show', $order->id) }}" class="bikube-os-btn bikube-os-btn-primary">Open</a>
                                    <a href="{{ url('/orders/' . $order->id . '/track') }}" class="bikube-os-btn bikube-os-btn-soft">Tracker</a>
                                </div>
                            </x-bikube.order-card>
                        @empty
                            <x-bikube.empty-state
                                title="No assigned orders now"
                                message="New assignments will appear here."
                            />
                        @endforelse
                    </div>
                </x-bikube.action-card>

                <x-bikube.action-card title="Available jobs" subtitle="Доступные задания">
                    <div class="space-y-3">
                        @forelse(($availableOrders ?? collect()) as $order)
                            @php
                                $meta = is_array($order->metadata ?? null) ? $order->metadata : [];
                                $eta = $order->scheduled_at
                                    ?? ($meta['eta_at'] ?? null)
                                    ?? ($meta['eta'] ?? null);
                                $etaFormatted = \Illuminate\Support\Carbon::make($eta)?->format('d.m H:i');
                            @endphp
                            <x-bikube.order-card
                                :title="$order->order_number ?? ('#'.$order->id)"
                                :meta="'ETA '.($etaFormatted ?? '—')"
                                :status="$order->status"
                                :payment="$order->payment_status"
                            >
                                <div class="bikube-os-info-grid">
                                    <div class="bikube-os-info">
                                        <p class="bikube-os-info-label">Pickup</p>
                                        <p class="bikube-os-info-value">{{ $meta['pickup_address'] ?? '—' }}</p>
                                    </div>
                                    <div class="bikube-os-info">
                                        <p class="bikube-os-info-label">Dropoff</p>
                                        <p class="bikube-os-info-value">{{ $meta['dropoff_address'] ?? '—' }}</p>
                                    </div>
                                </div>
                                <div class="mt-3 bikube-os-actions">
                                    <a href="{{ route('lk.orders.show', $order->id) }}" class="bikube-os-btn bikube-os-btn-primary">Open</a>
                                </div>
                            </x-bikube.order-card>
                        @empty
                            <x-bikube.empty-state
                                title="No available jobs right now"
                                message="Please check back later."
                            />
                        @endforelse
                    </div>
                </x-bikube.action-card>

                <x-bikube.action-card title="Динамика дохода (7 дней)" subtitle="Сводный график">
                    @php
                        $chartValues = array_values($earningsChart ?? []);
                        $chartMax = max(max($chartValues ?: [0]), 1);
                    @endphp
                    <div class="grid grid-cols-7 gap-2 items-end h-36">
                        @foreach(($earningsChart ?? []) as $date => $value)
                            @php
                                $height = max(8, (int) round(($value / $chartMax) * 100));
                            @endphp
                            <div class="flex flex-col items-center gap-2">
                                <div class="w-full rounded-t bg-gradient-to-t from-amber-500 to-amber-300" style="height: {{ $height }}px"></div>
                                <div class="text-[10px] font-semibold text-slate-500">{{ \Carbon\Carbon::parse($date)->format('d.m') }}</div>
                            </div>
                        @endforeach
                    </div>
                </x-bikube.action-card>
            </div>

            <x-bikube.action-card title="Quick links" subtitle="LK shortcuts">
                <div class="bikube-os-actions mb-4">
                    <a href="{{ route('lk.wallet') }}" class="bikube-os-btn bikube-os-btn-soft">Wallet</a>
                    <a href="{{ route('lk.support') }}" class="bikube-os-btn bikube-os-btn-soft">Support</a>
                    <a href="{{ route('lk.orders.index') }}" class="bikube-os-btn bikube-os-btn-soft">Orders</a>
                </div>

                <h3 class="bikube-os-card-title">Последние события</h3>
                <div class="space-y-3 max-h-[420px] overflow-y-auto pr-1 mt-3">
                    @forelse(($recentEvents ?? []) as $event)
                        <div class="bikube-os-info">
                            <p class="bikube-os-info-value">{{ $event['title'] ?? 'Событие' }}</p>
                            <p class="text-sm text-slate-600 mt-1">{{ $event['message'] ?? '' }}</p>
                            <p class="text-xs text-slate-500 mt-2">{{ $event['created_at'] ?? '' }}</p>
                        </div>
                    @empty
                        <x-bikube.empty-state title="Событий пока нет" />
                    @endforelse
                </div>
            </x-bikube.action-card>
        </section>
    </x-bikube.os-shell>
</div>
@endsection

@push('scripts')
<script>
    function workerDashboard() {
        return {
            isOnline: @json((bool) ($workerStatus->is_online ?? false)),
            todayEarnings: @json((float) ($todayEarnings ?? 0)),
            todayCompleted: @json((int) ($todayCompleted ?? 0)),
            hasActiveOrder: @json(($activeDeliveryOrderData ?? null) !== null || ($activeAssignmentData ?? null) !== null),
            init() {
                this.startAutoRefresh();
            },
            formatCurrency(value) {
                return new Intl.NumberFormat('nb-NO', {
                    style: 'currency',
                    currency: 'NOK',
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0,
                }).format(Number(value || 0));
            },
            async toggleStatus() {
                const next = !this.isOnline;
                try {
                    const response = await fetch('{{ route('lk.worker.status') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]')?.content ?? '',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ online: next }),
                    });
                    const payload = await response.json();
                    if (response.ok && payload.success) {
                        this.isOnline = next;
                    }
                } catch (error) {
                    console.error('Failed to toggle worker status', error);
                }
            },
            startAutoRefresh() {
                setInterval(async () => {
                    try {
                        const response = await fetch('{{ route('lk.dashboard.refresh') }}', {
                            headers: { 'Accept': 'application/json' },
                        });
                        const payload = await response.json();
                        if (response.ok && payload.success && payload.data) {
                            const data = payload.data;
                            this.isOnline = !!data.isOnline;
                            this.todayEarnings = Number(data.todayEarnings || 0);
                            this.todayCompleted = Number(data.todayCompleted || 0);
                            this.hasActiveOrder = !!data.hasActiveOrder;
                        }
                    } catch (error) {
                        console.error('Dashboard refresh failed', error);
                    }
                }, 15000);
            },
        };
    }
</script>
@endpush
