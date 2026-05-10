@extends('account.layout')

@section('title', "Р”РѕСЃС‚Р°РІРєР° Р·Р°РєР°Р·Р° #{$order->id}")
@section('header', "Р”РѕСЃС‚Р°РІРєР° #{$order->id}")

@section('content')
<div class="space-y-6">
    <a href="{{ route('account.deliveries.index') }}" class="btn btn-tertiary">← РљРѕ РІСЃРµРј РґРѕСЃС‚Р°РІРєР°Рј</a>

    <div class="grid gap-6 lg:grid-cols-3">
        <section class="card lg:col-span-1" aria-labelledby="delivery-meta-title">
            <div class="card-header">
                <h2 id="delivery-meta-title" class="card-title">РРЅС„РѕСЂРјР°С†РёСЏ Рѕ РґРѕСЃС‚Р°РІРєРµ</h2>
            </div>
            <div class="card-body space-y-3 text-sm text-slate-700">
                <p>РЎРѕР·РґР°РЅР°: <strong>{{ $order->created_at->format('d.m.Y H:i') }}</strong></p>
                <p>
                    РўРёРї:
                    <strong>
                        @switch($deliveryOrder->type->value ?? $deliveryOrder->type)
                            @case('grocery') РџСЂРѕРґСѓРєС‚С‹ @break
                            @case('bulky') РљСЂСѓРїРЅРѕРіР°Р±Р°СЂРёС‚ @break
                            @case('food') Р“РѕС‚РѕРІР°СЏ РµРґР° @break
                            @default {{ $deliveryOrder->type }}
                        @endswitch
                    </strong>
                </p>
                <p>
                    РЎС‚Р°С‚СѓСЃ:
                    <strong>
                        @switch($deliveryOrder->tracking_status->value ?? $deliveryOrder->tracking_status)
                            @case('pending') РћР¶РёРґР°РµС‚ @break
                            @case('assigned') РљСѓСЂСЊРµСЂ РЅР°Р·РЅР°С‡РµРЅ @break
                            @case('picked_up') Р—Р°РєР°Р· Р·Р°Р±СЂР°РЅ @break
                            @case('in_transit') Р’ РїСѓС‚Рё @break
                            @case('delivered') Р”РѕСЃС‚Р°РІР»РµРЅ @break
                            @case('cancelled') РћС‚РјРµРЅС‘РЅ @break
                            @default {{ $deliveryOrder->tracking_status }}
                        @endswitch
                    </strong>
                </p>

                @if($deliveryOrder->eta)
                    <p>ETA: <strong>{{ $deliveryOrder->eta->format('H:i') }}</strong></p>
                @endif

                @if($deliveryOrder->courier)
                    <p>РљСѓСЂСЊРµСЂ: <strong>{{ $deliveryOrder->courier->name }}</strong></p>
                @endif

                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <p class="text-xs uppercase tracking-wide text-slate-500">РђРґСЂРµСЃ Р·Р°Р±РѕСЂР°</p>
                    <p class="mt-1">{{ $deliveryOrder->pickup_address ?? '—' }}</p>
                </div>

                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <p class="text-xs uppercase tracking-wide text-slate-500">РђРґСЂРµСЃ РґРѕСЃС‚Р°РІРєРё</p>
                    <p class="mt-1">{{ $deliveryOrder->delivery_address ?? '—' }}</p>
                </div>

                @if($deliveryOrder->estimated_distance_km)
                    <p>Р Р°СЃСЃС‚РѕСЏРЅРёРµ: <strong>{{ number_format($deliveryOrder->estimated_distance_km, 1, ',', ' ') }} РєРј</strong></p>
                @endif
                @if($deliveryOrder->estimated_duration_minutes)
                    <p>Р”Р»РёС‚РµР»СЊРЅРѕСЃС‚СЊ: <strong>{{ $deliveryOrder->estimated_duration_minutes }} РјРёРЅ</strong></p>
                @endif
            </div>
        </section>

        <section class="card lg:col-span-2" aria-labelledby="delivery-tracking-title">
            <div class="card-header">
                <h2 id="delivery-tracking-title" class="card-title">РўСЂРµРєРёРЅРі</h2>
                <p class="card-subtitle">РћР±РЅРѕРІР»СЏРµС‚СЃСЏ РІ СЂРµР°Р»СЊРЅРѕРј РІСЂРµРјРµРЅРё.</p>
            </div>
            <div class="card-body">
                <x-delivery-tracking :order-id="$order->id" :delivery-order-id="$deliveryOrder->id" />
            </div>
        </section>
    </div>
</div>
@endsection
