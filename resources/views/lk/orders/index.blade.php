@extends('lk.layout')

@section('title', 'Мои заказы')

@section('content')
<x-bikube.os-shell>
    <x-bikube.page-header
        eyebrow="BiKuBe OS / LK"
        title="Мои заказы"
        subtitle="Track current assignments and completed history."
        badge="Wave 3B UI Core v2"
        :refresh-url="route('lk.orders.index', ['status' => $statusFilter ?? 'all'])"
        :open-url="route('lk.dashboard')"
        open-label="Back to dashboard"
        :chips="['Active', 'Scheduled', 'Completed', 'Ownership guard']"
    />

    <section class="bikube-os-grid-3">
        <x-bikube.kpi-card label="Активные" :value="$activeCount ?? 0" hint="Текущие задания" tone="blue" />
        <x-bikube.kpi-card label="План" :value="$upcomingCount ?? 0" hint="Запланированные задачи" tone="amber" />
        <x-bikube.kpi-card label="Выполнены" :value="$completedCount ?? 0" hint="Закрытые заказы" tone="emerald" />
    </section>

    <x-bikube.action-card title="Фильтр заказов" subtitle="Переключение между статусами">
        <div class="bikube-os-actions">
            @php
                $tabs = [
                    'active' => 'Активные',
                    'upcoming' => 'Запланированные',
                    'completed' => 'Завершенные',
                    'all' => 'Все',
                ];
            @endphp
            @foreach($tabs as $tab => $label)
                <a href="{{ route('lk.orders.index', ['status' => $tab]) }}"
                   class="bikube-os-btn {{ ($statusFilter ?? 'all') === $tab ? 'bikube-os-btn-primary' : 'bikube-os-btn-soft' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </x-bikube.action-card>

    <x-bikube.action-card title="Список заказов" subtitle="Назначенные задания исполнителя">
        @if($orders->isEmpty())
            <x-bikube.empty-state
                title="Нет заказов в этом разделе"
                message="Попробуйте переключить фильтр или вернитесь позже."
                action-label="Показать все заказы"
                :action-href="route('lk.orders.index', ['status' => 'all'])"
            />
            @if(!empty($isPrivilegedOps))
                <p class="text-xs text-slate-500 mt-3">
                    OPS roles in LK see only orders assigned to their own user ID in this list.
                </p>
            @endif
        @else
            <div class="space-y-3">
                @foreach($orders as $order)
                    @php
                        $pickupAddress = $order->pickup_address ?? ($order->metadata['pickup_address'] ?? null);
                        $dropoffAddress = $order->dropoff_address ?? ($order->metadata['dropoff_address'] ?? null);
                        $etaValue = $order->scheduled_at ?? ($order->metadata['eta_at'] ?? null) ?? ($order->metadata['eta'] ?? null);
                        $etaFormatted = \Illuminate\Support\Carbon::make($etaValue)?->format('d.m H:i');
                        $statusLabel = match ((string) $order->status) {
                            'assigned' => 'Назначен',
                            'in_progress' => 'В работе',
                            'completed' => 'Завершен',
                            'delivered' => 'Доставлен',
                            'pending' => 'Ожидает',
                            'confirmed' => 'Подтвержден',
                            'scheduled' => 'Запланирован',
                            'cancelled' => 'Отменен',
                            default => ucfirst((string) $order->status),
                        };
                    @endphp
                    <x-bikube.order-card
                        :title="$order->order_number ?? ('#'.$order->id)"
                        :meta="'Создан: '.($order->created_at?->format('d.m.Y H:i') ?? '—').' · ETA/SLA: '.($etaFormatted ?? optional($order->sla_deadline)->format('d.m H:i') ?? '—')"
                        :status="$statusLabel"
                        :payment="$order->payment_status"
                    >
                        <div class="bikube-os-info-grid">
                            <div class="bikube-os-info">
                                <p class="bikube-os-info-label">Город / адрес</p>
                                <p class="bikube-os-info-value">
                                    {{ $order->address?->city ?? 'Город не указан' }}
                                    @if($order->address?->address_line1)
                                        · {{ $order->address->address_line1 }}
                                    @endif
                                </p>
                            </div>
                            <div class="bikube-os-info">
                                <p class="bikube-os-info-label">Pickup</p>
                                <p class="bikube-os-info-value">{{ $pickupAddress ?: '—' }}</p>
                            </div>
                            <div class="bikube-os-info">
                                <p class="bikube-os-info-label">Dropoff</p>
                                <p class="bikube-os-info-value">{{ $dropoffAddress ?: '—' }}</p>
                            </div>
                            <div class="bikube-os-info">
                                <p class="bikube-os-info-label">Сумма / тип</p>
                                <p class="bikube-os-info-value">{{ number_format((float) ($order->total_amount ?? 0), 0, ',', ' ') }} kr · {{ $order->service_type ?? 'Доставка' }}</p>
                            </div>
                        </div>
                        <div class="mt-3 bikube-os-actions">
                            <a href="{{ route('lk.orders.show', $order) }}" class="bikube-os-btn bikube-os-btn-primary">Открыть</a>
                            <a href="{{ url('/orders/' . $order->id . '/track') }}" class="bikube-os-btn bikube-os-btn-soft">Tracker</a>
                        </div>
                    </x-bikube.order-card>
                @endforeach
            </div>
        @endif
    </x-bikube.action-card>

    @if($orders->hasPages())
        <div>
            {{ $orders->links() }}
        </div>
    @endif
</x-bikube.os-shell>
@endsection
