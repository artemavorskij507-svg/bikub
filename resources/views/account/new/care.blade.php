@extends('account.layout')

@section('title', 'РќРѕРІС‹Р№ СЃРѕС†РІРёР·РёС‚')
@section('header', 'РЎРѕС†РёР°Р»СЊРЅС‹Р№ РІРёР·РёС‚')

@section('content')
<div class="space-y-6 max-w-3xl">
    @if(isset($error))
        <div class="alert alert-warning" role="alert">
            <div class="alert-content"><p class="alert-text">{{ $error }}</p></div>
        </div>
    @endif

    @if($clients->isEmpty() || $careServices->isEmpty())
        <section class="card card-empty">
            <div class="card-empty-content">
                <h2 class="card-empty-title">РќРµРґРѕСЃС‚Р°С‚РѕС‡РЅРѕ РґР°РЅРЅС‹С… РґР»СЏ Р·Р°РїСЂРѕСЃР°</h2>
                <p class="card-empty-text">
                    @if($clients->isEmpty())
                        Р”Р»СЏ СЃРѕР·РґР°РЅРёСЏ Р·Р°РїСЂРѕСЃР° РЅР° СЃРѕС†РёР°Р»СЊРЅС‹Р№ РІРёР·РёС‚ РЅРµРѕР±С…РѕРґРёРјРѕ РёРјРµС‚СЊ РґРѕСЃС‚СѓРї Рє РїСЂРѕС„РёР»СЏРј РєР»РёРµРЅС‚РѕРІ.
                    @else
                        Р’ РґР°РЅРЅС‹Р№ РјРѕРјРµРЅС‚ РЅРµС‚ РґРѕСЃС‚СѓРїРЅС‹С… СѓСЃР»СѓРі СЃРѕС†РёР°Р»СЊРЅРѕР№ РїРѕРјРѕС‰Рё.
                    @endif
                </p>
            </div>
        </section>
    @else
        <section class="card">
            <div class="card-body">
                <p class="text-sm text-slate-600">Р’С‹ РјРѕР¶РµС‚Рµ РѕС„РѕСЂРјРёС‚СЊ РІРёР·РёС‚ РґР»СЏ РѕРґРЅРѕРіРѕ РёР· РґРѕСЃС‚СѓРїРЅС‹С… РїСЂРѕС„РёР»РµР№.</p>
            </div>
        </section>

        <section class="card" aria-labelledby="care-form-title">
            <div class="card-header">
                <h2 id="care-form-title" class="card-title">Р—Р°РїСЂРѕСЃ РЅР° СЃРѕС†РІРёР·РёС‚</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('account.new-order.care.store') }}" class="space-y-4" novalidate>
                    @csrf

                    <div class="form-group mb-0">
                        <label class="form-label">РљР»РёРµРЅС‚</label>
                        <select name="client_profile_id" class="form-select" required>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}" @if($activeClient && $activeClient->id === $client->id) selected @endif>{{ $client->full_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group mb-0">
                        <label class="form-label">РЈСЃР»СѓРіР° СЃРѕС†РїРѕРјРѕС‰Рё</label>
                        <select name="care_service_id" class="form-select" required>
                            @foreach($careServices as $service)
                                <option value="{{ $service->id }}">{{ $service->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="form-group mb-0">
                            <label class="form-label">Р”Р°С‚Р° Рё РІСЂРµРјСЏ</label>
                            <input type="datetime-local" name="scheduled_start_at" class="form-input" required>
                        </div>
                        <div class="form-group mb-0">
                            <label class="form-label">Р”Р»РёС‚РµР»СЊРЅРѕСЃС‚СЊ (РјРёРЅ)</label>
                            <input type="number" name="duration_minutes" min="30" max="600" class="form-input">
                        </div>
                    </div>

                    <div class="form-group mb-0">
                        <label class="form-label">РљРѕРјРјРµРЅС‚Р°СЂРёР№</label>
                        <textarea name="comment" rows="3" class="form-textarea"></textarea>
                    </div>

                    <div class="form-actions justify-end">
                        <button type="submit" class="btn btn-primary">РЎРѕР·РґР°С‚СЊ Р·Р°РїСЂРѕСЃ</button>
                    </div>
                </form>
            </div>
        </section>
    @endif
</div>
@endsection
