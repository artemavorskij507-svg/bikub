@extends('account.layout')

@section('title', 'РќРѕРІС‹Р№ Р·Р°РєР°Р· вЂ” РјР°СЃС‚РµСЂ')
@section('header', 'РњР°СЃС‚РµСЂ РЅР° РґРѕРј')

@section('content')
<div class="space-y-6 max-w-3xl">
    <section class="card">
        <div class="card-body">
            <p class="text-sm text-slate-600">
                @if($activeClient)
                    Р—Р°РєР°Р· Р±СѓРґРµС‚ СЃРѕР·РґР°РЅ РґР»СЏ: <strong>{{ $activeClient->full_name }}</strong>
                @else
                    Р—Р°РєР°Р· Р±СѓРґРµС‚ СЃРѕР·РґР°РЅ РґР»СЏ РІР°С€РµРіРѕ РїСЂРѕС„РёР»СЏ.
                @endif
            </p>
        </div>
    </section>

    <section class="card" aria-labelledby="handyman-form-title">
        <div class="card-header">
            <h2 id="handyman-form-title" class="card-title">Р—Р°РїСЂРѕСЃ РјР°СЃС‚РµСЂР°</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('account.new-order.handyman.store') }}" class="space-y-4" novalidate>
                @csrf

                <div class="form-group mb-0">
                    <label class="form-label">РђРґСЂРµСЃ <span class="form-required">*</span></label>
                    <input type="text" name="address" class="form-input" required>
                </div>

                <div class="form-group mb-0">
                    <label class="form-label">РћРїРёСЃР°РЅРёРµ СЂР°Р±РѕС‚ <span class="form-required">*</span></label>
                    <textarea name="description" rows="4" class="form-textarea" required></textarea>
                </div>

                <div class="form-group mb-0">
                    <label class="form-label">Р–РµР»Р°РµРјРѕРµ РІСЂРµРјСЏ (РѕРїС†РёРѕРЅР°Р»СЊРЅРѕ)</label>
                    <input type="datetime-local" name="scheduled_at" class="form-input">
                </div>

                <div class="form-group mb-0">
                    <label class="form-label">РљРѕРјРјРµРЅС‚Р°СЂРёР№</label>
                    <textarea name="comment" rows="3" class="form-textarea"></textarea>
                </div>

                <div class="form-actions justify-end">
                    <button type="submit" class="btn btn-primary">РЎРѕР·РґР°С‚СЊ Р·Р°СЏРІРєСѓ</button>
                </div>
            </form>
        </div>
    </section>
</div>
@endsection
