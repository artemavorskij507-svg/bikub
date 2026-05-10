@extends('account.layout')

@section('title', 'РќРѕРІС‹Р№ Р·Р°РєР°Р· вЂ” Р›РёС‡РЅС‹Р№ РєР°Р±РёРЅРµС‚')
@section('header', 'РќРѕРІС‹Р№ Р·Р°РєР°Р·')

@section('content')
<div class="space-y-6">
    <section class="card">
        <div class="card-body">
            @if($activeClient)
                <p class="text-sm text-slate-600">Р’С‹ РѕС„РѕСЂРјР»СЏРµС‚Рµ Р·Р°РєР°Р· РґР»СЏ: <strong class="text-slate-900">{{ $activeClient->full_name }}</strong></p>
            @else
                <p class="text-sm text-slate-600">Р—Р°РєР°Р· Р±СѓРґРµС‚ РѕС„РѕСЂРјР»РµРЅ РґР»СЏ РІР°С€РµРіРѕ РїСЂРѕС„РёР»СЏ. РџСЂРѕС„РёР»СЊ РјРѕР¶РЅРѕ РїРѕРјРµРЅСЏС‚СЊ РІ РїРµСЂРµРєР»СЋС‡Р°С‚РµР»Рµ РІС‹С€Рµ.</p>
            @endif
        </div>
    </section>

    <section class="grid gap-4 sm:grid-cols-2" aria-label="Р’С‹Р±РѕСЂ С‚РёРїР° Р·Р°РєР°Р·Р°">
        <a href="{{ route('account.new-order.delivery') }}" class="card card-interactive p-5">
            <h2 class="text-lg font-semibold text-slate-900">Р”РѕСЃС‚Р°РІРєР° РїСЂРѕРґСѓРєС‚РѕРІ</h2>
            <p class="mt-2 text-sm text-slate-600">РџРѕРєСѓРїРєРё РІ РјР°РіР°Р·РёРЅР°С… СЃ РґРѕСЃС‚Р°РІРєРѕР№ РґРѕ РґРІРµСЂРё.</p>
        </a>

        <a href="{{ route('account.new-order.eco') }}" class="card card-interactive p-5">
            <h2 class="text-lg font-semibold text-slate-900">Р­РєРѕ-РІС‹РІРѕР·</h2>
            <p class="mt-2 text-sm text-slate-600">Р’С‹РІРѕР· РєСЂСѓРїРЅРѕРіР°Р±Р°СЂРёС‚Р° Рё РїСЂР°РІРёР»СЊРЅР°СЏ СѓС‚РёР»РёР·Р°С†РёСЏ.</p>
        </a>

        <a href="{{ route('account.new-order.handyman') }}" class="card card-interactive p-5">
            <h2 class="text-lg font-semibold text-slate-900">РњР°СЃС‚РµСЂ РЅР° РґРѕРј</h2>
            <p class="mt-2 text-sm text-slate-600">Р‘С‹С‚РѕРІС‹Рµ Рё СЂРµРјРѕРЅС‚РЅС‹Рµ СЂР°Р±РѕС‚С‹ РїРѕ Р·Р°РїСЂРѕСЃСѓ.</p>
        </a>

        @if($hasSocialCareAccess)
            <a href="{{ route('account.new-order.care') }}" class="card card-interactive p-5">
                <h2 class="text-lg font-semibold text-slate-900">РЎРѕС†РёР°Р»СЊРЅС‹Р№ РІРёР·РёС‚</h2>
                <p class="mt-2 text-sm text-slate-600">Р’РёР·РёС‚ РїРѕРјРѕС‰РЅРёРєР° РґР»СЏ РїРѕРґРѕРїРµС‡РЅРѕРіРѕ СЃ РєРѕРЅС‚СЂРѕР»РµРј РєР°С‡РµСЃС‚РІР°.</p>
            </a>
        @endif
    </section>
</div>
@endsection
