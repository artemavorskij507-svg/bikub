@extends('layouts.app')

@section('title', 'Partner Portal')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <x-bikube.os-shell>
        <x-bikube.page-header
            eyebrow="BiKuBe OS / Partner"
            title="Partner Operations Dashboard"
            subtitle="Unified partner cockpit for orders, contracts and payouts."
            badge="Wave 3B UI Core v1"
            :refresh-url="url('/partner')"
            :open-url="url('/partner/orders')"
            open-label="Open orders"
            :chips="['Partner workload', 'Payout visibility', 'Contract summary']"
        />

        @if($profileWarning)
            <x-bikube.action-card title="Partner profile notice" subtitle="Access context">
                <p class="text-sm text-amber-800">{{ $profileWarning }}</p>
            </x-bikube.action-card>
        @endif

        <section class="bikube-os-grid-5">
            <x-bikube.kpi-card label="Total orders" :value="$kpi['total'] ?? 0" hint="All linked orders" tone="slate" />
            <x-bikube.kpi-card label="Active" :value="$kpi['active'] ?? 0" hint="Pending and in progress" tone="blue" />
            <x-bikube.kpi-card label="Completed" :value="$kpi['completed'] ?? 0" hint="Delivered and closed" tone="emerald" />
            <x-bikube.kpi-card label="Cancelled / disputed" :value="$kpi['cancelled'] ?? 0" hint="Requires review" tone="red" />
            <x-bikube.kpi-card label="Payment issues" :value="$kpi['payment_issues'] ?? 0" hint="Failed or refunded" tone="violet" />
        </section>

        <section class="bikube-os-grid-3">
            <x-bikube.kpi-card label="Contracts" :value="$summary['contracts_total'] ?? 0" hint="Linked partner contracts" tone="blue" />
            <x-bikube.kpi-card label="Payouts total" :value="$summary['payouts_total'] ?? 0" hint="All payout requests" tone="slate" />
            <x-bikube.kpi-card label="Payouts pending" :value="$summary['payouts_pending'] ?? 0" hint="Pending / approved / processing" tone="amber" />
        </section>

        <x-bikube.action-card title="Recent partner orders" subtitle="Operational queue snapshot">
            @if($orders->isEmpty())
                <x-bikube.empty-state
                    title="No partner orders yet"
                    message="As soon as orders are linked to this partner profile, they will appear here."
                    action-label="Open full list"
                    :action-href="url('/partner/orders')"
                />
            @else
                <div class="space-y-3">
                    @foreach($orders as $order)
                        <x-bikube.order-card
                            :title="$order->order_number ?? ('Order #' . $order->id)"
                            :meta="'ID: '.$order->id.' · Created: '.(optional($order->created_at)->format('d.m.Y H:i') ?? '—')"
                            :status="$order->status"
                            :payment="$order->payment_status"
                            :priority="$order->priority"
                        >
                            <div class="bikube-os-info-grid">
                                <div class="bikube-os-info">
                                    <p class="bikube-os-info-label">Customer</p>
                                    <p class="bikube-os-info-value">{{ $order->user->name ?? $order->user->email ?? ('User #' . ($order->user_id ?? '—')) }}</p>
                                </div>
                                <div class="bikube-os-info">
                                    <p class="bikube-os-info-label">Service</p>
                                    <p class="bikube-os-info-value">{{ $order->service_type ?? '—' }}</p>
                                </div>
                                <div class="bikube-os-info">
                                    <p class="bikube-os-info-label">Worker</p>
                                    <p class="bikube-os-info-value">{{ $order->assignedUser->name ?? '—' }}</p>
                                </div>
                                <div class="bikube-os-info">
                                    <p class="bikube-os-info-label">Pickup</p>
                                    <p class="bikube-os-info-value">{{ $order->address->street_address ?? $order->address->formatted_address ?? '—' }}</p>
                                </div>
                            </div>
                        </x-bikube.order-card>
                    @endforeach
                </div>
            @endif
        </x-bikube.action-card>
    </x-bikube.os-shell>
</div>
@endsection
