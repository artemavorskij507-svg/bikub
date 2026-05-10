@extends('account.layout')

@section('title', 'РњРѕР№ СЂРµРјРѕРЅС‚ вЂ” '.$project->title)
@section('header', 'РњРѕР№ СЂРµРјРѕРЅС‚')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-slate-900">РњРѕР№ СЂРµРјРѕРЅС‚</h2>
            <p class="text-sm text-slate-600">Р—Р°РєР°Р· #{{ $order->order_number ?? $order->id }}</p>
        </div>
        <a href="{{ route('account.orders.show', $order) }}" class="btn btn-secondary">РќР°Р·Р°Рґ Рє Р·Р°РєР°Р·Сѓ</a>
    </div>

    <section class="card p-6 space-y-4">
        <div class="grid gap-4 md:grid-cols-3">
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-500">РќР°Р·РІР°РЅРёРµ</p>
                <p class="mt-1 text-xl font-semibold text-slate-900">{{ $project->title }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-500">РЎС‚Р°С‚СѓСЃ</p>
                <span class="mt-1 inline-flex items-center rounded-full bg-primary-50 px-3 py-1 text-sm font-medium text-primary-700">{{ $project->status }}</span>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-500">РџСЂРѕРіСЂРµСЃСЃ</p>
                <p class="mt-1 text-3xl font-bold text-primary-600">{{ $project->overall_progress_percent ?? 0 }}%</p>
            </div>
        </div>

        <div class="grid gap-4 text-sm md:grid-cols-4">
            <div><p class="text-xs uppercase text-slate-500">РђРґСЂРµСЃ</p><p class="text-slate-900">{{ $project->address_line }}, {{ $project->city }}</p></div>
            <div><p class="text-xs uppercase text-slate-500">РџР»Р°РЅРѕРІС‹Рµ РґР°С‚С‹</p><p class="text-slate-900">{{ optional($project->planned_start_at)->format('d.m.Y') }} – {{ optional($project->planned_finish_at)->format('d.m.Y') }}</p></div>
            <div><p class="text-xs uppercase text-slate-500">Р¤Р°РєС‚РёС‡РµСЃРєРёРµ РґР°С‚С‹</p><p class="text-slate-900">{{ optional($project->actual_start_at)->format('d.m.Y') }} – {{ optional($project->actual_finish_at)->format('d.m.Y') }}</p></div>
            <div><p class="text-xs uppercase text-slate-500">Р СѓРєРѕРІРѕРґРёС‚РµР»СЊ</p><p class="text-slate-900">{{ $project->projectManager?->user?->name ?? 'РќР°Р·РЅР°С‡Р°РµС‚СЃСЏ' }}</p></div>
        </div>
    </section>

    <section class="card p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">РўР°Р№РјР»Р°Р№РЅ СЌС‚Р°РїРѕРІ</h2>
        <ul class="space-y-3">
            @foreach($project->stages as $stage)
                <li class="rounded-xl border border-slate-200 p-4">
                    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="font-semibold text-slate-900">{{ $stage->name }}</p>
                            <p class="text-sm text-slate-600">РЎС‚Р°С‚СѓСЃ: {{ $stage->status }} @if($stage->progress_percent !== null) · {{ $stage->progress_percent }}%@endif</p>
                        </div>
                        <p class="text-sm text-slate-500">{{ optional($stage->planned_start_at)->format('d.m.Y') }} @if($stage->planned_finish_at) – {{ $stage->planned_finish_at->format('d.m.Y') }}@endif</p>
                    </div>
                </li>
            @endforeach
            @if($project->stages->isEmpty())
                <li class="text-sm text-slate-500">Р­С‚Р°РїС‹ РїРѕРєР° РЅРµ РґРѕР±Р°РІР»РµРЅС‹.</li>
            @endif
        </ul>
    </section>

    <section class="card p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">РћР±РЅРѕРІР»РµРЅРёСЏ</h2>
        <ul class="space-y-3">
            @forelse($project->updates->sortByDesc('created_at') as $update)
                <li class="rounded-xl border border-slate-200 p-4">
                    <div class="mb-2 flex justify-between text-sm text-slate-500">
                        <span>{{ $update->created_at->format('d.m.Y H:i') }}</span>
                        @if($update->author)<span>{{ $update->author->name }}</span>@endif
                    </div>
                    @if($update->title)<p class="font-semibold text-slate-900">{{ $update->title }}</p>@endif
                    @if($update->body)<p class="mt-1 text-sm text-slate-700">{{ $update->body }}</p>@endif
                    <div class="mt-2 text-xs text-slate-500 space-x-3">
                        @if($update->progress_percent !== null)<span>РџСЂРѕРіСЂРµСЃСЃ: {{ $update->progress_percent }}%</span>@endif
                        @if($update->stage)<span>Р­С‚Р°Рї: {{ $update->stage->name }}</span>@endif
                    </div>
                </li>
            @empty
                <li class="text-sm text-slate-500">РћР±РЅРѕРІР»РµРЅРёР№ РїРѕРєР° РЅРµС‚.</li>
            @endforelse
        </ul>
    </section>

    <section class="card p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Р¤РѕС‚Рѕ РїСЂРѕРµРєС‚Р°</h2>
        <div class="grid gap-4 grid-cols-2 md:grid-cols-4">
            @forelse($project->media as $media)
                <article class="overflow-hidden rounded-xl border border-slate-200">
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk($media->disk)->url($media->thumbnail_path ?: $media->path) }}" alt="{{ $media->caption }}" class="h-40 w-full object-cover">
                    @if($media->caption || $media->role)
                        <div class="p-3 text-xs text-slate-600 space-y-1">
                            @if($media->role)
                                <p class="font-semibold">
                                    @switch($media->role)
                                        @case('before') Р”Рѕ @break
                                        @case('during') Р’ РїСЂРѕС†РµСЃСЃРµ @break
                                        @case('after') РџРѕСЃР»Рµ @break
                                        @default {{ $media->role }}
                                    @endswitch
                                </p>
                            @endif
                            @if($media->caption)<p>{{ $media->caption }}</p>@endif
                        </div>
                    @endif
                </article>
            @empty
                <p class="text-sm text-slate-500 col-span-2 md:col-span-4">Р¤РѕС‚Рѕ РїРѕРєР° РЅРµ РґРѕР±Р°РІР»РµРЅС‹.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
