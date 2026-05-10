@extends('account.layout')

@section('title', 'РџРѕСЂСѓС‡РµРЅРёРµ РѕС‚РїСЂР°РІР»РµРЅРѕ вЂ” Р›РёС‡РЅС‹Р№ РєР°Р±РёРЅРµС‚')
@section('header', 'РџРѕСЂСѓС‡РµРЅРёРµ РѕС‚РїСЂР°РІР»РµРЅРѕ РЅР° РїСЂРѕРІРµСЂРєСѓ')

@section('content')
<div class="space-y-6 max-w-3xl">
    @if(session('status'))
        <div class="alert alert-success" role="status" aria-live="polite">
            <div class="alert-content"><p class="alert-text">{{ session('status') }}</p></div>
        </div>
    @endif

    <section class="card" aria-labelledby="errand-thankyou-title">
        <div class="card-header">
            <h2 id="errand-thankyou-title" class="card-title">Р—Р°РїСЂРѕСЃ РїСЂРёРЅСЏС‚ РІ СЂР°Р±РѕС‚Сѓ</h2>
            <p class="card-subtitle">Р”РёСЃРїРµС‚С‡РµСЂ РїСЂРѕРІРµСЂРёС‚ РґРµС‚Р°Р»Рё Рё РЅР°Р·РЅР°С‡РёС‚ РёСЃРїРѕР»РЅРёС‚РµР»СЏ.</p>
        </div>

        <div class="card-body space-y-4">
            @if($order->errandTask)
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-500">РљСЂР°С‚РєРѕРµ РѕРїРёСЃР°РЅРёРµ</p>
                    <p class="mt-1 text-sm font-medium text-slate-900">{{ \Illuminate\Support\Str::limit($order->errandTask->description, 180) }}</p>
                </div>

                <div class="grid gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-500">РўРёРї РїРѕСЂСѓС‡РµРЅРёСЏ</p>
                        <p class="mt-1">{{ $order->errandTask->category }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-500">РџСЂРµРґРІР°СЂРёС‚РµР»СЊРЅР°СЏ РѕС†РµРЅРєР°</p>
                        <p class="mt-1">
                            @if($order->errandTask->estimated_total_amount)
                                ~ {{ number_format($order->errandTask->estimated_total_amount, 2, ',', ' ') }} NOK
                            @else
                                Р‘СѓРґРµС‚ СЂР°СЃСЃС‡РёС‚Р°РЅР° РґРёСЃРїРµС‚С‡РµСЂРѕРј
                            @endif
                        </p>
                    </div>
                </div>
            @endif

            <p class="text-xs text-slate-500">Р’С‹ РїРѕР»СѓС‡РёС‚Рµ СѓРІРµРґРѕРјР»РµРЅРёРµ, РєРѕРіРґР° РїРѕСЂСѓС‡РµРЅРёРµ РїРµСЂРµР№РґС‘С‚ РІ СЃС‚Р°С‚СѓСЃ РІС‹РїРѕР»РЅРµРЅРёСЏ.</p>
        </div>

        <div class="card-footer">
            <a href="{{ route('account.errands.index') }}" class="btn btn-primary">РџРµСЂРµР№С‚Рё Рє РјРѕРёРј РїРѕСЂСѓС‡РµРЅРёСЏРј</a>
        </div>
    </section>
</div>
@endsection
