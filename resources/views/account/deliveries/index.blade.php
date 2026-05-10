@extends('account.layout')

@section('title', 'РњРѕРё РґРѕСЃС‚Р°РІРєРё')
@section('header', 'РњРѕРё РґРѕСЃС‚Р°РІРєРё')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-slate-600">РћС‚СЃР»РµР¶РёРІР°Р№С‚Рµ СЃС‚Р°С‚СѓСЃС‹, ETA Рё РґРµС‚Р°Р»Рё РґРѕСЃС‚Р°РІРѕРє.</p>
        <a href="{{ route('account.deliveries.create') }}" class="btn btn-primary">РќРѕРІР°СЏ РґРѕСЃС‚Р°РІРєР°</a>
    </div>

    @if($deliveries->isEmpty())
        <div class="card card-empty">
            <div class="card-empty-content">
                <h2 class="card-empty-title">Р”РѕСЃС‚Р°РІРѕРє РїРѕРєР° РЅРµС‚</h2>
                <p class="card-empty-text">РљРѕРіРґР° РІС‹ СЃРѕР·РґР°РґРёС‚Рµ РїРµСЂРІСѓСЋ РґРѕСЃС‚Р°РІРєСѓ, РѕРЅР° РїРѕСЏРІРёС‚СЃСЏ Р·РґРµСЃСЊ.</p>
                <a href="{{ route('account.deliveries.create') }}" class="btn btn-primary">РЎРѕР·РґР°С‚СЊ РґРѕСЃС‚Р°РІРєСѓ</a>
            </div>
        </div>
    @else
        <section class="card" aria-labelledby="deliveries-title">
            <div class="card-header">
                <h2 id="deliveries-title" class="card-title">РЎРїРёСЃРѕРє РґРѕСЃС‚Р°РІРѕРє</h2>
                <p class="card-subtitle">РќР°Р¶РјРёС‚Рµ РЅР° РґРѕСЃС‚Р°РІРєСѓ, С‡С‚РѕР±С‹ РїРµСЂРµР№С‚Рё Рє РґРµС‚Р°Р»СЏРј Рё С‚СЂРµРєРёРЅРіСѓ.</p>
            </div>

            <div class="divide-y divide-slate-200">
                @foreach($deliveries as $delivery)
                    <a href="{{ route('account.deliveries.show', $delivery) }}" class="block p-4 transition-colors hover:bg-slate-50 md:p-6">
                        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                            <div>
                                <p class="text-xs text-slate-500">Р—Р°РєР°Р· #{{ $delivery->order_id }} · {{ $delivery->order?->created_at?->format('d.m.Y H:i') }}</p>
                                <p class="mt-1 text-sm text-slate-700">
                                    РўРёРї:
                                    <strong>
                                        @switch($delivery->type->value ?? $delivery->type)
                                            @case('grocery') РџСЂРѕРґСѓРєС‚С‹ @break
                                            @case('bulky') РљСЂСѓРїРЅРѕРіР°Р±Р°СЂРёС‚ @break
                                            @case('food') Р“РѕС‚РѕРІР°СЏ РµРґР° @break
                                            @default {{ $delivery->type }}
                                        @endswitch
                                    </strong>
                                </p>
                                <p class="mt-1 text-sm text-slate-700">РђРґСЂРµСЃ: {{ $delivery->delivery_address ?? '—' }}</p>
                            </div>

                            <div class="text-left md:text-right">
                                <p class="text-xs text-slate-500">РЎС‚Р°С‚СѓСЃ РґРѕСЃС‚Р°РІРєРё</p>
                                <span class="mt-1 inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium
                                    @class([
                                        'bg-gray-100 text-gray-800' => $delivery->tracking_status->value === 'pending',
                                        'bg-yellow-100 text-yellow-800' => $delivery->tracking_status->value === 'assigned',
                                        'bg-blue-100 text-blue-800' => in_array($delivery->tracking_status->value, ['picked_up', 'in_transit']),
                                        'bg-green-100 text-green-800' => $delivery->tracking_status->value === 'delivered',
                                        'bg-red-100 text-red-800' => $delivery->tracking_status->value === 'cancelled',
                                    ])">
                                    @switch($delivery->tracking_status->value ?? $delivery->tracking_status)
                                        @case('pending') РћР¶РёРґР°РµС‚ @break
                                        @case('assigned') РќР°Р·РЅР°С‡РµРЅ @break
                                        @case('picked_up') Р—Р°Р±СЂР°РЅ @break
                                        @case('in_transit') Р’ РїСѓС‚Рё @break
                                        @case('delivered') Р”РѕСЃС‚Р°РІР»РµРЅ @break
                                        @case('cancelled') РћС‚РјРµРЅС‘РЅ @break
                                        @default {{ $delivery->tracking_status }}
                                    @endswitch
                                </span>
                                @if($delivery->eta)
                                    <p class="mt-2 text-xs text-slate-500">ETA: {{ $delivery->eta->format('H:i') }}</p>
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            @if($deliveries->hasPages())
                <div class="card-footer">
                    {{ $deliveries->links() }}
                </div>
            @endif
        </section>
    @endif
</div>
@endsection
