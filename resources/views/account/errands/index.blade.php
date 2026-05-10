@extends('account.layout')

@section('title', 'РњРѕРё РёРЅРґРёРІРёРґСѓР°Р»СЊРЅС‹Рµ РїРѕСЂСѓС‡РµРЅРёСЏ вЂ” Р›РёС‡РЅС‹Р№ РєР°Р±РёРЅРµС‚')
@section('header', 'РњРѕРё РёРЅРґРёРІРёРґСѓР°Р»СЊРЅС‹Рµ РїРѕСЂСѓС‡РµРЅРёСЏ')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-slate-600">РџРµСЂСЃРѕРЅР°Р»СЊРЅС‹Рµ Р·Р°РґР°С‡Рё РґР»СЏ РїРѕРјРѕС‰РЅРёРєР°: РґРѕРєСѓРјРµРЅС‚С‹, РїРѕРєСѓРїРєРё, РІРёР·РёС‚С‹.</p>
        <a href="{{ route('account.errands.create') }}" class="btn btn-primary">РЎРѕР·РґР°С‚СЊ РїРѕСЂСѓС‡РµРЅРёРµ</a>
    </div>

    @if(session('status'))
        <div class="alert alert-success" role="status" aria-live="polite">
            <div class="alert-content"><p class="alert-text">{{ session('status') }}</p></div>
        </div>
    @endif

    @if($errands->isEmpty())
        <div class="card card-empty">
            <div class="card-empty-content">
                <h2 class="card-empty-title">РџРѕСЂСѓС‡РµРЅРёР№ РїРѕРєР° РЅРµС‚</h2>
                <p class="card-empty-text">РЎРѕР·РґР°Р№С‚Рµ РїРµСЂРІРѕРµ РїРѕСЂСѓС‡РµРЅРёРµ, Рё РјС‹ РІРѕР·СЊРјС‘Рј РµРіРѕ РІ СЂР°Р±РѕС‚Сѓ.</p>
                <a href="{{ route('account.errands.create') }}" class="btn btn-primary">РќРѕРІРѕРµ РїРѕСЂСѓС‡РµРЅРёРµ</a>
            </div>
        </div>
    @else
        <section class="card" aria-labelledby="errands-list-title">
            <div class="card-header">
                <h2 id="errands-list-title" class="card-title">РЎРїРёСЃРѕРє РїРѕСЂСѓС‡РµРЅРёР№</h2>
            </div>
            <div class="divide-y divide-slate-200">
                @foreach($errands as $errand)
                    <a href="{{ route('account.errands.show', $errand) }}" class="block p-4 transition-colors hover:bg-slate-50 md:p-5">
                        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                            <div>
                                <p class="text-xs text-slate-500">#{{ $errand->id }} · {{ $errand->created_at->format('d.m.Y H:i') }}</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900">{{ \Illuminate\Support\Str::limit($errand->description, 120) }}</p>

                                <div class="mt-2 flex flex-wrap gap-2 text-xs text-slate-600">
                                    @if($errand->category)
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5">{{ $errand->category }}</span>
                                    @endif
                                    @if($errand->is_urgent)
                                        <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-red-700">РЎСЂРѕС‡РЅРѕ</span>
                                    @endif
                                    @if($errand->requires_trusted_helper)
                                        <span class="inline-flex items-center rounded-full bg-yellow-100 px-2 py-0.5 text-yellow-800">РџСЂРѕРІРµСЂРµРЅРЅС‹Р№ РїРѕРјРѕС‰РЅРёРє</span>
                                    @endif
                                </div>
                            </div>

                            <div class="text-left md:text-right">
                                @if($errand->order)
                                    <p class="text-xs text-slate-500">{{ $errand->order->status ?? '' }}</p>
                                @endif
                                @if($errand->total_estimated_price)
                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ number_format($errand->total_estimated_price / 100, 2, ',', ' ') }} kr</p>
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
            <div class="card-footer">{{ $errands->links() }}</div>
        </section>
    @endif
</div>
@endsection
