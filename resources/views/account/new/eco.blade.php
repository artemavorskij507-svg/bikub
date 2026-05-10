@extends('account.layout')

@section('title', 'РќРѕРІС‹Р№ Р·Р°РєР°Р· вЂ” СЌРєРѕ-РІС‹РІРѕР·')
@section('header', 'Р­РєРѕ-РІС‹РІРѕР·')

@section('content')
<div class="space-y-6 max-w-3xl">
    <section class="card">
        <div class="card-body">
            <p class="text-sm text-slate-600">
                @if($activeClient)
                    Р—Р°РєР°Р· РѕС„РѕСЂРјР»СЏРµС‚СЃСЏ РґР»СЏ: <strong>{{ $activeClient->full_name }}</strong>
                @else
                    Р—Р°РєР°Р· РѕС„РѕСЂРјР»СЏРµС‚СЃСЏ РґР»СЏ РІР°С€РµРіРѕ РїСЂРѕС„РёР»СЏ.
                @endif
            </p>
        </div>
    </section>

    <section class="card" aria-labelledby="eco-form-title">
        <div class="card-header">
            <h2 id="eco-form-title" class="card-title">Р—Р°СЏРІРєР° РЅР° СЌРєРѕ-РІС‹РІРѕР·</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('account.new-order.eco.store') }}" class="space-y-4" novalidate>
                @csrf

                <div class="form-group mb-0">
                    <label class="form-label">РђРґСЂРµСЃ РІС‹РІРѕР·Р° <span class="form-required">*</span></label>
                    <input type="text" name="address" class="form-input" required>
                </div>

                <div id="eco-items" class="space-y-2">
                    <label class="form-label">Р§С‚Рѕ РЅСѓР¶РЅРѕ РІС‹РІРµР·С‚Рё</label>
                    <div class="grid gap-2 sm:grid-cols-[1fr_96px]">
                        <input type="text" name="items[0][name]" placeholder="РќР°РїСЂРёРјРµСЂ, РґРёРІР°РЅ" class="form-input" required>
                        <input type="number" name="items[0][quantity]" value="1" min="1" class="form-input" required>
                    </div>
                </div>

                <button type="button" id="add-eco-item" class="btn btn-secondary btn-sm">+ Р”РѕР±Р°РІРёС‚СЊ РїСЂРµРґРјРµС‚</button>

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

@push('scripts')
<script>
document.getElementById('add-eco-item').addEventListener('click', function () {
    const container = document.getElementById('eco-items');
    const index = container.querySelectorAll('input[name^="items"]').length / 2;
    const wrapper = document.createElement('div');
    wrapper.className = 'grid gap-2 mt-2 sm:grid-cols-[1fr_96px]';
    wrapper.innerHTML = `
        <input type="text" name="items[${index}][name]" class="form-input" placeholder="РџСЂРµРґРјРµС‚" required>
        <input type="number" name="items[${index}][quantity]" value="1" min="1" class="form-input" required>
    `;
    container.appendChild(wrapper);
});
</script>
@endpush
