@extends('account.layout')

@section('title', 'Обзор — Личный кабинет')
@section('header', 'Кабинет клиента')

@section('content')
<x-bikube.os-shell>
    <x-bikube.page-header
        eyebrow="BiKuBe OS / Account"
        :title="'Здравствуйте, '.(auth()->user()->name ?? 'User').'.'"
        subtitle="Customer cockpit for active orders, tracking and support."
        badge="Wave 3B UI Core v1"
        :refresh-url="route('account.dashboard')"
        :open-url="route('account.orders.index')"
        open-label="Open orders"
        :chips="['Order tracking', 'Support center', 'Unified customer workspace']"
    >
        <x-slot:actions>
            <a href="{{ route('account.new-order.index') }}" class="bikube-os-btn bikube-os-btn-soft">New order</a>
            @if(\Illuminate\Support\Facades\Route::has('account.classifieds.my-ads'))
                <a href="{{ route('account.classifieds.my-ads') }}" class="bikube-os-btn bikube-os-btn-soft">Classifieds</a>
            @endif
        </x-slot:actions>
    </x-bikube.page-header>

    <section class="bikube-os-grid-3">
        <x-bikube.kpi-card label="Active orders" :value="$kpi['active'] ?? 0" hint="Currently in progress" tone="blue" />
        <x-bikube.kpi-card label="Completed (30d)" :value="$kpi['completed'] ?? 0" hint="Finished and closed" tone="emerald" />
        <x-bikube.kpi-card label="Total orders" :value="$kpi['total'] ?? 0" hint="Full account history" tone="slate" />
    </section>

    <section class="bikube-os-grid-3">
        <x-bikube.action-card class="lg:col-span-2" title="Active and recent orders" subtitle="Quick access to tracker and details">
            @if($orderCards->isEmpty())
                <x-bikube.empty-state
                    title="No orders yet"
                    message="When you create your first order, it will appear here."
                    action-label="Create order"
                    :action-href="route('account.new-order.index')"
                />
            @else
                <div class="space-y-3">
                    @foreach($orderCards as $order)
                        <x-bikube.order-card
                            :title="'#'.$order['order_number'].' · '.($order['service_label'] ?? $order['title'])"
                            :meta="'Created: '.($order['created_at']?->format('d.m.Y H:i') ?? '—')"
                            :status="$order['status'] ?? $order['status_label']"
                            :payment="$order['payment_status'] ?? null"
                            :priority="$order['priority'] ?? null"
                        >
                            <div class="bikube-os-info-grid">
                                <div class="bikube-os-info">
                                    <p class="bikube-os-info-label">Scenario</p>
                                    <p class="bikube-os-info-value">{{ $order['scenario_key'] ?? '—' }}</p>
                                </div>
                                <div class="bikube-os-info">
                                    <p class="bikube-os-info-label">Status label</p>
                                    <p class="bikube-os-info-value">{{ $order['status_label'] ?? '—' }}</p>
                                </div>
                            </div>
                            <div class="mt-3 bikube-os-actions">
                                <a href="{{ route('account.orders.track', $order['id']) }}" class="bikube-os-btn bikube-os-btn-primary">Open tracker</a>
                                <a href="{{ route('account.orders.show', $order['id']) }}" class="bikube-os-btn bikube-os-btn-soft">Open order</a>
                            </div>
                        </x-bikube.order-card>
                    @endforeach
                </div>
            @endif
        </x-bikube.action-card>

        <div class="space-y-4">
            <x-bikube.action-card title="Quick actions" subtitle="Account shortcuts">
                <div class="bikube-os-actions">
                    <a href="{{ route('account.orders.index') }}" class="bikube-os-btn bikube-os-btn-soft">Order history</a>
                    <a href="{{ route('account.claims.index') }}" class="bikube-os-btn bikube-os-btn-soft">Support / claims</a>
                    @if(\Illuminate\Support\Facades\Route::has('account.classifieds.my-ads'))
                        <a href="{{ route('account.classifieds.my-ads') }}" class="bikube-os-btn bikube-os-btn-soft">My classifieds</a>
                    @endif
                    <a href="{{ route('account.notifications.index') }}" class="bikube-os-btn bikube-os-btn-soft">Notifications</a>
                </div>
            </x-bikube.action-card>

            <x-bikube.action-card title="Timeline" subtitle="Latest account events">
                @if(($timeline ?? collect())->isEmpty())
                    <x-bikube.empty-state
                        title="No events yet"
                        message="New account activity will appear here automatically."
                    />
                @else
                    <div class="space-y-3">
                        @foreach($timeline as $event)
                            <div class="bikube-os-info">
                                <p class="bikube-os-info-label">{{ $event['created_at']->format('d.m.Y H:i') }}</p>
                                <p class="bikube-os-info-value">{{ $event['title'] }}</p>
                                @if(!empty($event['body']))
                                    <p class="text-sm text-slate-600 mt-1">{{ $event['body'] }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-bikube.action-card>
        </div>
    </section>
</x-bikube.os-shell>
@endsection
