@extends('account.layout')

@section('title', 'РџРѕСЂСѓС‡РµРЅРёРµ #' . $errand->id . ' вЂ” Р›РёС‡РЅС‹Р№ РєР°Р±РёРЅРµС‚')
@section('header', 'РџРѕСЂСѓС‡РµРЅРёРµ #' . $errand->id)

@section('content')
<div class="space-y-6">
    <a href="{{ route('account.errands.index') }}" class="btn btn-tertiary">← Р’РµСЂРЅСѓС‚СЊСЃСЏ Рє СЃРїРёСЃРєСѓ</a>

    @if(session('status'))
        <div class="alert alert-success" role="status" aria-live="polite">
            <div class="alert-content"><p class="alert-text">{{ session('status') }}</p></div>
        </div>
    @endif

    @if($errand->order)
        <div class="card p-4">
            <p class="text-sm text-slate-600">РЎС‚Р°С‚СѓСЃ: <strong>{{ $errand->order->status }}</strong> · РЎРѕР·РґР°РЅРѕ: {{ $errand->created_at->format('d.m.Y H:i') }}</p>
        </div>
    @endif

    <section class="grid gap-4 lg:grid-cols-2">
        <article class="card p-4">
            <h2 class="text-sm font-semibold mb-2">РћРїРёСЃР°РЅРёРµ Р·Р°РґР°С‡Рё</h2>
            <p class="text-sm whitespace-pre-line">{{ $errand->description }}</p>
            @if($errand->category)
                <p class="mt-2 text-xs text-slate-500">РљР°С‚РµРіРѕСЂРёСЏ: {{ $errand->category }}</p>
            @endif
        </article>

        <article class="card p-4">
            <h2 class="text-sm font-semibold mb-2">РњР°СЂС€СЂСѓС‚</h2>
            <p class="text-sm"><span class="text-slate-500">РћС‚РєСѓРґР°:</span> {{ $errand->from_address ?: '—' }}</p>
            <p class="text-sm"><span class="text-slate-500">РљСѓРґР°:</span> {{ $errand->to_address ?: '—' }}</p>
        </article>

        <article class="card p-4 lg:col-span-2">
            <h2 class="text-sm font-semibold mb-2">РџР°СЂР°РјРµС‚СЂС‹ РїРѕСЂСѓС‡РµРЅРёСЏ</h2>
            <dl class="grid grid-cols-1 gap-x-6 gap-y-2 text-sm sm:grid-cols-2">
                <div><dt class="text-slate-500">РЎСЂРѕС‡РЅРѕРµ</dt><dd>{{ $errand->is_urgent ? 'Р”Р°' : 'РќРµС‚' }}</dd></div>
                <div><dt class="text-slate-500">РўРѕР»СЊРєРѕ РїСЂРѕРІРµСЂРµРЅРЅС‹Р№ РїРѕРјРѕС‰РЅРёРє</dt><dd>{{ $errand->requires_trusted_helper ? 'Р”Р°' : 'РќРµС‚' }}</dd></div>
                <div><dt class="text-slate-500">РџРѕРґРїРёСЃСЊ / РґРѕРєСѓРјРµРЅС‚С‹</dt><dd>{{ $errand->requires_signature ? 'Р”Р°' : 'РќРµС‚' }}</dd></div>
                <div><dt class="text-slate-500">Р Р°Р±РѕС‚Р° СЃ РґРѕРєСѓРјРµРЅС‚Р°РјРё</dt><dd>{{ $errand->involves_documents ? 'Р”Р°' : 'РќРµС‚' }}</dd></div>
                <div><dt class="text-slate-500">РћР¶РёРґР°РµРјР°СЏ РґР»РёС‚РµР»СЊРЅРѕСЃС‚СЊ</dt><dd>{{ $errand->expected_duration_minutes ?? '—' }} РјРёРЅ</dd></div>
                <div><dt class="text-slate-500">РЎР»РѕР¶РЅРѕСЃС‚СЊ</dt><dd>{{ $errand->complexity_level ?? '—' }} / 5</dd></div>
                <div><dt class="text-slate-500">РђРІР°РЅСЃ</dt><dd>@if($errand->material_advance_amount){{ number_format($errand->material_advance_amount / 100, 2, ',', ' ') }} kr @else — @endif</dd></div>
            </dl>
        </article>

        <article class="card p-4 lg:col-span-2">
            <h2 class="text-sm font-semibold mb-2">РћС†РµРЅРєР° СЃС‚РѕРёРјРѕСЃС‚Рё</h2>
            @if($errand->total_estimated_price)
                <p class="text-lg font-semibold text-slate-900">{{ number_format($errand->total_estimated_price / 100, 2, ',', ' ') }} kr</p>
                <p class="mt-1 text-xs text-slate-500">Р­С‚Рѕ РїСЂРµРґРІР°СЂРёС‚РµР»СЊРЅР°СЏ РѕС†РµРЅРєР°.</p>
            @else
                <p class="text-sm text-slate-600">РћС†РµРЅРєР° СЃС‚РѕРёРјРѕСЃС‚Рё РїРѕРєР° РЅРµ СЂР°СЃСЃС‡РёС‚Р°РЅР°.</p>
            @endif
        </article>
    </section>
</div>
@endsection
