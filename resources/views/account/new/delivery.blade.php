@extends('account.layout')

@section('title', 'РќРѕРІС‹Р№ Р·Р°РєР°Р· вЂ” РґРѕСЃС‚Р°РІРєР°')
@section('header', 'Р”РѕСЃС‚Р°РІРєР° РїСЂРѕРґСѓРєС‚РѕРІ')

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

    <section class="card" aria-labelledby="delivery-order-form-title">
        <div class="card-header">
            <h2 id="delivery-order-form-title" class="card-title">РћС„РѕСЂРјР»РµРЅРёРµ РґРѕСЃС‚Р°РІРєРё</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('account.new-order.delivery.store') }}" novalidate>
                @csrf

                <div class="form-group">
                    <label class="form-label" for="store-select">РњР°РіР°Р·РёРЅ</label>
                    <select name="store_id" id="store-select" class="form-select">
                        @foreach($stores as $store)
                            <option value="{{ $store->id }}">{{ $store->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="delivery-address-input">РђРґСЂРµСЃ РґРѕСЃС‚Р°РІРєРё <span class="form-required">*</span></label>
                    <input type="text" name="address" id="delivery-address-input" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Р–РµР»Р°РµРјРѕРµ РІСЂРµРјСЏ (РѕРїС†РёРѕРЅР°Р»СЊРЅРѕ)</label>
                    <input type="datetime-local" name="scheduled_at" class="form-input">
                </div>

                <div class="form-group">
                    <label class="form-label">РљРѕРјРјРµРЅС‚Р°СЂРёР№</label>
                    <textarea name="comment" rows="3" class="form-textarea"></textarea>
                </div>

                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="is_vulnerable_client" value="1" class="form-checkbox">
                    РљР»РёРµРЅС‚ СѓСЏР·РІРёРјС‹Р№, С‚СЂРµР±СѓРµС‚СЃСЏ РѕСЃРѕР±РѕРµ РІРЅРёРјР°РЅРёРµ
                </label>

                <div class="form-actions justify-end">
                    <button type="submit" class="btn btn-primary">РЎРѕР·РґР°С‚СЊ Р·Р°РєР°Р·</button>
                </div>
            </form>
        </div>
    </section>

    <section class="card" aria-labelledby="delivery-quote-title">
        <div class="card-header">
            <h2 id="delivery-quote-title" class="card-title">Р‘С‹СЃС‚СЂС‹Р№ СЂР°СЃС‡С‘С‚</h2>
            <p class="card-subtitle">РџСЂРѕРІРµСЂСЊС‚Рµ РѕСЂРёРµРЅС‚РёСЂРѕРІРѕС‡РЅСѓСЋ СЃС‚РѕРёРјРѕСЃС‚СЊ РґРѕСЃС‚Р°РІРєРё РґРѕ РѕС„РѕСЂРјР»РµРЅРёСЏ.</p>
        </div>
        <div class="card-body space-y-4">
            <div class="form-group mb-0">
                <label class="form-label" for="quote-cart-total">РЎСѓРјРјР° РєРѕСЂР·РёРЅС‹ (NOK)</label>
                <input type="number" id="quote-cart-total" value="500" min="0" step="10" class="form-input">
            </div>

            <button type="button" id="delivery-quote-btn" class="btn btn-secondary">Р Р°СЃСЃС‡РёС‚Р°С‚СЊ СЃС‚РѕРёРјРѕСЃС‚СЊ</button>
            <div id="delivery-quote-output" class="hidden text-sm text-slate-600"></div>
        </div>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const quoteBtn = document.getElementById('delivery-quote-btn');
    const storeSelect = document.getElementById('store-select');
    const addressInput = document.getElementById('delivery-address-input');
    const cartInput = document.getElementById('quote-cart-total');
    const output = document.getElementById('delivery-quote-output');

    if (!quoteBtn) {
        return;
    }

    quoteBtn.addEventListener('click', async () => {
        output.classList.remove('text-red-600');
        output.textContent = 'РЎС‡РёС‚Р°РµРј СЃС‚РѕРёРјРѕСЃС‚СЊ...';
        output.classList.remove('hidden');

        const payload = {
            type: 'grocery',
            store_id: Number(storeSelect.value),
            delivery_address: addressInput.value,
            items: [{
                product_id: 0,
                quantity: 1,
                unit_price: Number(cartInput.value) || 0,
            }],
        };

        try {
            const response = await fetch('/api/v1/delivery/quote', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            });

            if (!response.ok) {
                const error = await response.json();
                throw new Error(error.message ?? 'РќРµ СѓРґР°Р»РѕСЃСЊ СЂР°СЃСЃС‡РёС‚Р°С‚СЊ РґРѕСЃС‚Р°РІРєСѓ');
            }

            const data = await response.json();
            const quote = data.data ?? {};
            const route = quote.route ?? {};

            output.innerHTML = `
                <div><span class="text-slate-500">РС‚РѕРіРѕ:</span> <strong>${(quote.total ?? 0).toFixed(2)} ${quote.currency ?? 'NOK'}</strong></div>
                <div class="text-slate-500">Р”РёСЃС‚Р°РЅС†РёСЏ: ${route.distance_km ?? '—'} РєРј · Р’СЂРµРјСЏ: ${route.duration_minutes ?? '—'} РјРёРЅ</div>
                <div class="text-slate-500">ETA: ${route.eta ?? '—'}</div>
            `;
        } catch (error) {
            output.classList.add('text-red-600');
            output.textContent = error.message;
        }
    });
});
</script>
@endsection
