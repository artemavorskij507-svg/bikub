@extends('layouts.app')

@section('title', 'Partner Orders')

@section('content')
@php
    $actionLabels = [
        'accepted' => 'Accept',
        'preparing' => 'Preparing',
        'ready' => 'Ready',
        'handed_to_courier' => 'Handed to courier',
        'cancelled' => 'Cancel',
    ];
@endphp

<div class="max-w-7xl mx-auto px-4 py-8">
    <x-bikube.os-shell>
        <x-bikube.page-header
            eyebrow="BiKuBe OS / Partner"
            title="Partner Orders"
            subtitle="Partner-owned order queue with lifecycle controls."
            badge="Wave 3B UI Core v1"
            :refresh-url="url('/partner/orders')"
            :open-url="url('/partner')"
            open-label="Back to dashboard"
            :chips="['Ownership guard', 'Status updates', 'Payment visibility']"
        />

        @if($profileWarning)
            <x-bikube.action-card title="Partner profile notice" subtitle="Access context">
                <p class="text-sm text-amber-800">{{ $profileWarning }}</p>
            </x-bikube.action-card>
        @endif

        @if(session('status'))
            <x-bikube.action-card title="Status update">
                <p class="text-sm text-emerald-800">{{ session('status') }}</p>
            </x-bikube.action-card>
        @endif

        @if($orders->isEmpty())
            <x-bikube.empty-state
                title="No assigned partner orders"
                message="As soon as orders are linked to this partner profile, they will appear here."
                action-label="Open dashboard"
                :action-href="url('/partner')"
            />
        @else
            <section class="space-y-4">
                @foreach($orders as $order)
                    <x-bikube.order-card
                        :title="$order->order_number ?? ('Order #' . $order->id)"
                        :meta="'ID: '.$order->id.' · Created: '.(optional($order->created_at)->format('d.m.Y H:i') ?? '—').($order->scheduled_at ? ' · Scheduled: '.optional($order->scheduled_at)->format('d.m.Y H:i') : '')"
                        :status="$order->status"
                        :payment="$order->payment_status"
                        :priority="$order->priority"
                    >
                        <div class="bikube-os-info-grid">
                            <div class="bikube-os-info">
                                <p class="bikube-os-info-label">Service</p>
                                <p class="bikube-os-info-value">{{ $order->service_type ?? '—' }}</p>
                            </div>
                            <div class="bikube-os-info">
                                <p class="bikube-os-info-label">Customer</p>
                                <p class="bikube-os-info-value">{{ $order->user->name ?? $order->user->email ?? ('User #' . ($order->user_id ?? '—')) }}</p>
                            </div>
                            <div class="bikube-os-info">
                                <p class="bikube-os-info-label">Pickup</p>
                                <p class="bikube-os-info-value">{{ $order->address->street_address ?? $order->address->formatted_address ?? '—' }}</p>
                            </div>
                            <div class="bikube-os-info">
                                <p class="bikube-os-info-label">Assigned worker</p>
                                <p class="bikube-os-info-value">{{ $order->assignedUser->name ?? '—' }}</p>
                            </div>
                        </div>

                        <div class="mt-4">
                            <p class="bikube-os-info-label mb-2">Partner status actions</p>
                            <div class="bikube-os-actions">
                                @foreach($actionLabels as $value => $label)
                                    <form method="POST" action="{{ url('/partner/orders/' . $order->id . '/status') }}">
                                        @csrf
                                        <input type="hidden" name="status" value="{{ $value }}">
                                        <button type="submit" class="bikube-os-btn {{ $value === 'cancelled' ? 'bikube-os-btn-danger' : 'bikube-os-btn-soft' }}">
                                            {{ $label }}
                                        </button>
                                    </form>
                                @endforeach
                                <a href="{{ url('/admin/orders/' . $order->id) }}" class="bikube-os-btn bikube-os-btn-primary">Open order</a>
                            </div>
                        </div>
                    </x-bikube.order-card>
                @endforeach
            </section>

            <div class="mt-4">
                {{ $orders->links() }}
            </div>
        @endif
    </x-bikube.os-shell>
</div>
@endsection
