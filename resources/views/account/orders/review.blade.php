@extends('account.layout')

@section('title', 'РћС†РµРЅРёС‚СЊ Р·Р°РєР°Р· вЂ” GLF Bikube')
@section('header', 'РћС†РµРЅРєР° Р·Р°РєР°Р·Р°')

@section('content')
<div class="space-y-6 max-w-3xl">
    <a href="{{ route('account.orders.show', $order) }}" class="btn btn-tertiary">← Р’РµСЂРЅСѓС‚СЊСЃСЏ Рє Р·Р°РєР°Р·Сѓ</a>

    <section class="card" aria-labelledby="review-form-title">
        <div class="card-header">
            <h2 id="review-form-title" class="card-title">РћС†РµРЅРёС‚СЊ СЂР°Р±РѕС‚Сѓ РјР°СЃС‚РµСЂР°</h2>
            <p class="card-subtitle">
                Р—Р°РєР°Р· №{{ $order->order_number ?? $order->id }} Р·Р°РІРµСЂС€С‘РЅ. Р’Р°С€Р° РѕР±СЂР°С‚РЅР°СЏ СЃРІСЏР·СЊ РІР»РёСЏРµС‚ РЅР° РєР°С‡РµСЃС‚РІРѕ СЃРµСЂРІРёСЃР°.
            </p>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('account.orders.review.store', $order) }}" novalidate>
                @csrf

                <div class="form-group">
                    <label class="form-label">РћС†РµРЅРєР°</label>
                    <div class="flex flex-wrap gap-3" role="radiogroup" aria-label="РћС†РµРЅРєР° Р·Р°РєР°Р·Р°">
                        @for($i = 1; $i <= 5; $i++)
                            <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                <input type="radio" name="rating" value="{{ $i }}" class="form-radio" {{ old('rating', 5) == $i ? 'checked' : '' }}>
                                <span>{{ $i }}</span>
                            </label>
                        @endfor
                    </div>
                    @error('rating')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-group">
                    <label for="comment" class="form-label">РљРѕРјРјРµРЅС‚Р°СЂРёР№ (РѕРїС†РёРѕРЅР°Р»СЊРЅРѕ)</label>
                    <textarea id="comment" name="comment" rows="4" class="form-textarea">{{ old('comment') }}</textarea>
                    @error('comment')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-actions justify-end">
                    <a href="{{ route('account.orders.show', $order) }}" class="btn btn-secondary">РћС‚РјРµРЅР°</a>
                    <button type="submit" class="btn btn-primary">РћС‚РїСЂР°РІРёС‚СЊ РѕС‚Р·С‹РІ</button>
                </div>
            </form>
        </div>
    </section>
</div>
@endsection
