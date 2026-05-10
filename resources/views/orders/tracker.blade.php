@extends('layouts.app')

@section('title', 'Order Tracker')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-semibold mb-2">Order {{ $order->order_number }}</h1>
    <p class="text-slate-600 mb-6">
        {{ $scenario['public_title'] ?? $order->service_type }} · Status: <strong>{{ $order->status }}</strong> · Payment: <strong>{{ $order->payment_status }}</strong>
    </p>

    @if($order->assignedUser)
        <div class="mb-6 text-sm text-slate-700">Assigned worker: {{ $order->assignedUser->name }}</div>
    @endif

    <div class="rounded-xl border border-slate-200 bg-white">
        <div class="px-4 py-3 border-b border-slate-100 font-medium">Timeline</div>
        <div class="divide-y divide-slate-100">
            @forelse($order->events as $event)
                <div class="px-4 py-3 text-sm">
                    <div class="font-medium">{{ $event->event_type }}</div>
                    <div class="text-slate-500">{{ $event->from_status }} → {{ $event->to_status }}</div>
                    <div class="text-slate-400">{{ $event->created_at }}</div>
                </div>
            @empty
                <div class="px-4 py-4 text-sm text-slate-500">No timeline events yet.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection

