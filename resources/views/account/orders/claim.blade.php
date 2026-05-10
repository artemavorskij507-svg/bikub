@extends('account.layout')

@section('title', 'РћСЃС‚Р°РІРёС‚СЊ РїСЂРµС‚РµРЅР·РёСЋ РїРѕ Р·Р°РєР°Р·Сѓ #' . $order->order_number . ' вЂ” Р›РёС‡РЅС‹Р№ РєР°Р±РёРЅРµС‚')
@section('header', 'РћСЃС‚Р°РІРёС‚СЊ РїСЂРµС‚РµРЅР·РёСЋ РїРѕ Р·Р°РєР°Р·Сѓ #' . $order->order_number)

@section('content')
<div class="space-y-6">
    <a href="{{ route('account.orders.show', $order) }}" class="btn btn-tertiary">← РќР°Р·Р°Рґ Рє Р·Р°РєР°Р·Сѓ</a>

    @if(session('status'))
        <div class="alert alert-success" role="status" aria-live="polite">
            <div class="alert-content">
                <p class="alert-title">Р“РѕС‚РѕРІРѕ</p>
                <p class="alert-text">{{ session('status') }}</p>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-error" role="alert" aria-live="assertive">
            <div class="alert-content">
                <p class="alert-title">РћС€РёР±РєРё РІР°Р»РёРґР°С†РёРё</p>
                <ul class="mt-2 list-disc pl-5 text-sm">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <section class="card" aria-labelledby="claim-form-title">
        <div class="card-header">
            <h2 id="claim-form-title" class="card-title">РџРѕРґР°С‚СЊ РїСЂРµС‚РµРЅР·РёСЋ</h2>
            <p class="card-subtitle">Р—Р°РєР°Р· #{{ $order->order_number }}. РњС‹ РїРµСЂРµРґР°РґРёРј Р·Р°РїСЂРѕСЃ РІ СЃР»СѓР¶Р±Сѓ РїРѕРґРґРµСЂР¶РєРё.</p>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('account.orders.claim.store', $order) }}" novalidate>
                @csrf

                <div class="form-group">
                    <label for="type" class="form-label">РўРёРї РїСЂРµС‚РµРЅР·РёРё <span class="form-required">*</span></label>
                    <select name="type" id="type" class="form-select" required>
                        <option value="">Р’С‹Р±РµСЂРёС‚Рµ С‚РёРї РїСЂРµС‚РµРЅР·РёРё</option>
                        <option value="quality" {{ old('type') === 'quality' ? 'selected' : '' }}>РљР°С‡РµСЃС‚РІРѕ СЂР°Р±РѕС‚</option>
                        <option value="damage" {{ old('type') === 'damage' ? 'selected' : '' }}>РџРѕРІСЂРµР¶РґРµРЅРёСЏ</option>
                        <option value="delay" {{ old('type') === 'delay' ? 'selected' : '' }}>Р—Р°РґРµСЂР¶РєР° РІС‹РїРѕР»РЅРµРЅРёСЏ</option>
                        <option value="billing" {{ old('type') === 'billing' ? 'selected' : '' }}>РџСЂРѕР±Р»РµРјС‹ СЃ РѕРїР»Р°С‚РѕР№</option>
                        <option value="other" {{ old('type') === 'other' ? 'selected' : '' }}>Р”СЂСѓРіРѕРµ</option>
                    </select>
                    @error('type')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-group">
                    <label for="severity" class="form-label">РљСЂРёС‚РёС‡РЅРѕСЃС‚СЊ</label>
                    <select name="severity" id="severity" class="form-select">
                        <option value="">Р’С‹Р±РµСЂРёС‚Рµ РєСЂРёС‚РёС‡РЅРѕСЃС‚СЊ</option>
                        <option value="low" {{ old('severity') === 'low' ? 'selected' : '' }}>РќРёР·РєР°СЏ</option>
                        <option value="medium" {{ old('severity') === 'medium' ? 'selected' : '' }}>РЎСЂРµРґРЅСЏСЏ</option>
                        <option value="high" {{ old('severity') === 'high' ? 'selected' : '' }}>Р’С‹СЃРѕРєР°СЏ</option>
                    </select>
                    @error('severity')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-group">
                    <label for="title" class="form-label">Р—Р°РіРѕР»РѕРІРѕРє РїСЂРµС‚РµРЅР·РёРё <span class="form-required">*</span></label>
                    <input type="text" name="title" id="title" class="form-input" value="{{ old('title') }}" required>
                    @error('title')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-group">
                    <label for="description" class="form-label">РћРїРёСЃР°РЅРёРµ РїСЂРѕР±Р»РµРјС‹ <span class="form-required">*</span></label>
                    <textarea name="description" id="description" rows="6" class="form-textarea" required>{{ old('description') }}</textarea>
                    <p class="form-hint">РћРїРёС€РёС‚Рµ РїРѕРґСЂРѕР±РЅРѕ СЃРёС‚СѓР°С†РёСЋ, С‡С‚РѕР±С‹ СѓСЃРєРѕСЂРёС‚СЊ СЂР°Р·Р±РѕСЂ.</p>
                    @error('description')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-actions justify-end">
                    <a href="{{ route('account.orders.show', $order) }}" class="btn btn-secondary">РћС‚РјРµРЅР°</a>
                    <button type="submit" class="btn btn-primary">РћС‚РїСЂР°РІРёС‚СЊ РїСЂРµС‚РµРЅР·РёСЋ</button>
                </div>
            </form>
        </div>
    </section>
</div>
@endsection
