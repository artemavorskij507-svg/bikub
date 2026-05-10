@extends('account.layout')

@section('title', 'РќРѕРІРѕРµ РёРЅРґРёРІРёРґСѓР°Р»СЊРЅРѕРµ РїРѕСЂСѓС‡РµРЅРёРµ вЂ” Р›РёС‡РЅС‹Р№ РєР°Р±РёРЅРµС‚')
@section('header', 'РќРѕРІРѕРµ РёРЅРґРёРІРёРґСѓР°Р»СЊРЅРѕРµ РїРѕСЂСѓС‡РµРЅРёРµ')

@section('content')
<div class="space-y-6 max-w-4xl">
    <p class="text-sm text-slate-600">РћРїРёС€РёС‚Рµ Р·Р°РґР°С‡Сѓ РєР°Рє РјРѕР¶РЅРѕ С‚РѕС‡РЅРµРµ. Р­С‚Рѕ СѓСЃРєРѕСЂРёС‚ РѕС†РµРЅРєСѓ Рё РЅР°Р·РЅР°С‡РµРЅРёРµ РёСЃРїРѕР»РЅРёС‚РµР»СЏ.</p>

    @if($errors->any())
        <div class="alert alert-error" role="alert" aria-live="assertive">
            <div class="alert-content">
                <p class="alert-title">РџСЂРѕРІРµСЂСЊС‚Рµ Р·Р°РїРѕР»РЅРµРЅРёРµ С„РѕСЂРјС‹</p>
                <ul class="mt-2 list-disc pl-5 text-sm">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <section class="card" aria-labelledby="errand-create-title">
        <div class="card-header">
            <h2 id="errand-create-title" class="card-title">Р¤РѕСЂРјР° РїРѕСЂСѓС‡РµРЅРёСЏ</h2>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('account.errands.store') }}" class="space-y-6" novalidate>
                @csrf

                <div class="form-group mb-0">
                    <label class="form-label">Р§С‚Рѕ РЅСѓР¶РЅРѕ СЃРґРµР»Р°С‚СЊ? <span class="form-required">*</span></label>
                    <textarea name="description" rows="5" required class="form-textarea" placeholder="РќР°РїСЂРёРјРµСЂ: Р·Р°Р±СЂР°С‚СЊ РґРѕРєСѓРјРµРЅС‚С‹ РІ РѕС„РёСЃРµ...">{{ old('description') }}</textarea>
                </div>

                <div class="form-group mb-0">
                    <label class="form-label">РљР°С‚РµРіРѕСЂРёСЏ (РЅРµРѕР±СЏР·Р°С‚РµР»СЊРЅРѕ)</label>
                    <select name="category" class="form-select">
                        <option value="">Р’С‹Р±РµСЂРёС‚Рµ РєР°С‚РµРіРѕСЂРёСЋ...</option>
                        <option value="courier" @selected(old('category') === 'courier')>РљСѓСЂСЊРµСЂСЃРєРѕРµ РїРѕСЂСѓС‡РµРЅРёРµ</option>
                        <option value="documents" @selected(old('category') === 'documents')>Р”РѕРєСѓРјРµРЅС‚С‹ / РїРѕРґРїРёСЃРё</option>
                        <option value="shopping" @selected(old('category') === 'shopping')>РџРѕРєСѓРїРєР° / РґРѕСЃС‚Р°РІРєР°</option>
                        <option value="queue" @selected(old('category') === 'queue')>РџРѕСЃС‚РѕСЏС‚СЊ РІ РѕС‡РµСЂРµРґРё</option>
                        <option value="visit" @selected(old('category') === 'visit')>Р›РёС‡РЅС‹Р№ РІРёР·РёС‚ / РїСЂРёСЃСѓС‚СЃС‚РІРёРµ</option>
                        <option value="other" @selected(old('category') === 'other')>Р”СЂСѓРіРѕРµ</option>
                    </select>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="form-group mb-0">
                        <label class="form-label">РћС‚РєСѓРґР° (Р°РґСЂРµСЃ)</label>
                        <input type="text" name="from_address" value="{{ old('from_address') }}" class="form-input" placeholder="РђРґСЂРµСЃ СЃС‚Р°СЂС‚Р°">
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label">РљСѓРґР° (Р°РґСЂРµСЃ)</label>
                        <input type="text" name="to_address" value="{{ old('to_address') }}" class="form-input" placeholder="РђРґСЂРµСЃ РєРѕРЅРµС‡РЅРѕР№ С‚РѕС‡РєРё">
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="form-group mb-0">
                        <label class="form-label">Р–РµР»Р°РµРјРѕРµ РІСЂРµРјСЏ РЅР°С‡Р°Р»Р°</label>
                        <input type="datetime-local" name="desired_start_at" value="{{ old('desired_start_at') }}" class="form-input">
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label">РћР¶РёРґР°РµРјР°СЏ РґР»РёС‚РµР»СЊРЅРѕСЃС‚СЊ (РјРёРЅСѓС‚С‹)</label>
                        <input type="number" name="expected_duration_minutes" value="{{ old('expected_duration_minutes', 60) }}" min="0" max="1440" class="form-input">
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-3 text-sm">
                    <label class="inline-flex items-center gap-2"><input type="checkbox" id="is_urgent" name="is_urgent" value="1" @checked(old('is_urgent')) class="form-checkbox">РЎСЂРѕС‡РЅРѕРµ РїРѕСЂСѓС‡РµРЅРёРµ</label>
                    <label class="inline-flex items-center gap-2"><input type="checkbox" id="requires_trusted_helper" name="requires_trusted_helper" value="1" @checked(old('requires_trusted_helper')) class="form-checkbox">РўРѕР»СЊРєРѕ РїСЂРѕРІРµСЂРµРЅРЅС‹Р№ РїРѕРјРѕС‰РЅРёРє</label>
                    <label class="inline-flex items-center gap-2"><input type="checkbox" id="requires_signature" name="requires_signature" value="1" @checked(old('requires_signature')) class="form-checkbox">РќСѓР¶РЅР° РїРѕРґРїРёСЃСЊ / РґРѕРєСѓРјРµРЅС‚С‹</label>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" id="involves_documents" name="involves_documents" value="1" @checked(old('involves_documents')) class="form-checkbox">Р Р°Р±РѕС‚Р° СЃ РѕС„РёС†РёР°Р»СЊРЅС‹РјРё РґРѕРєСѓРјРµРЅС‚Р°РјРё</label>
                    <div class="form-group mb-0">
                        <label class="form-label">РЎР»РѕР¶РЅРѕСЃС‚СЊ (1-5)</label>
                        <input type="number" name="complexity_level" value="{{ old('complexity_level', 2) }}" min="1" max="5" class="form-input">
                    </div>
                </div>

                <div class="form-group mb-0">
                    <label class="form-label">РђРІР°РЅСЃ РЅР° РїРѕРєСѓРїРєРё (kr)</label>
                    <input type="number" name="material_advance_amount" value="{{ old('material_advance_amount', 0) }}" min="0" class="form-input">
                    <p class="form-hint">РњР°РєСЃРёРјР°Р»СЊРЅР°СЏ СЃСѓРјРјР° РґР»СЏ Р·Р°РєСѓРїРєРё РјР°С‚РµСЂРёР°Р»РѕРІ РёР»Рё С‚РѕРІР°СЂРѕРІ.</p>
                </div>

                <div class="form-actions justify-end">
                    <button type="submit" class="btn btn-primary">РћС‚РїСЂР°РІРёС‚СЊ РїРѕСЂСѓС‡РµРЅРёРµ</button>
                </div>
            </form>
        </div>
    </section>
</div>
@endsection
