@extends('layouts.app')

@section('content')
    <div class="bg-slate-100 py-10">
        <div class="container mx-auto max-w-6xl px-4 space-y-8">
            <header>
                <p class="text-sm uppercase tracking-wider text-primary-600">{{ __('Забота и помощь') }}</p>
                <h1 class="mt-2 text-3xl font-semibold text-slate-900">
                    {{ __('Мои подопечные и визиты') }}
                </h1>
                <p class="mt-2 text-slate-600">
                    {{ __('Следите за планами ухода, предстоящими визитами и отчётами помощников в одном месте.') }}
                </p>
            </header>

            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-900">
                    {{ session('status') }}
                </div>
            @endif

            @if ($clients->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white/70 px-6 py-10 text-center shadow-sm">
                    <h2 class="text-xl font-semibold text-slate-800">{{ __('Нет подключённых профилей') }}</h2>
                    <p class="mt-3 text-slate-500">
                        {{ __('Если вы оформили заявку на социальную помощь, координатор свяжется с вами и откроет доступ к личному кабинету.') }}
                    </p>
                    <a href="{{ route('public.service', ['slug' => 'social-care']) }}"
                       class="mt-6 inline-flex items-center justify-center rounded-full bg-primary-600 px-5 py-2 text-sm font-semibold text-white shadow hover:bg-primary-500">
                        {{ __('Узнать об услуге') }}
                    </a>
                </div>
            @else
                <div class="grid gap-6 md:grid-cols-2">
                    @foreach ($clients as $card)
                        <div class="flex flex-col gap-5 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                        {{ $card['relationship'] === 'self' ? __('Ваш профиль') : __('Вы доверенное лицо') }}
                                    </p>
                                    <h2 class="mt-1 text-xl font-semibold text-slate-900">
                                        {{ $card['client']->full_name }}
                                    </h2>
                                    <p class="text-sm text-slate-500">
                                        {{ $card['client']->city ?? __('город не указан') }}
                                    </p>
                                </div>
                                <span class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-600">
                                    {{ trans_choice(':count активный план|:count активных плана|:count активных планов', $card['active_plan_count'], ['count' => $card['active_plan_count']]) }}
                                </span>
                            </div>

                            <div class="space-y-2">
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
                                    {{ __('Ближайшие визиты') }}
                                </h3>
                                @forelse ($card['upcoming_visits'] as $visit)
                                    <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2">
                                        <div class="flex items-center justify-between text-sm">
                                            <div>
                                                <p class="font-semibold text-slate-800">
                                                    {{ optional($visit->scheduled_start_at)->format('d.m H:i') ?? __('Время уточняется') }}
                                                </p>
                                                <p class="text-slate-500">
                                                    {{ $visit->careService?->name ?? __('Услуга не указана') }}
                                                </p>
                                            </div>
                                            <span class="inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-500">
                                                {{ $visit->care_status ?? 'SCHEDULED' }}
                                            </span>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-sm text-slate-500">{{ __('Запланированных визитов нет.') }}</p>
                                @endforelse
                            </div>

                            <div class="space-y-2">
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
                                    {{ __('Последние визиты') }}
                                </h3>
                                @forelse ($card['recent_visits'] as $visit)
                                    <div class="text-sm text-slate-500">
                                        {{ optional($visit->scheduled_start_at)->format('d.m') ?? '—' }} ·
                                        {{ $visit->careService?->name ?? __('Услуга') }} ·
                                        {{ $visit->care_status ?? '—' }}
                                    </div>
                                @empty
                                    <p class="text-sm text-slate-500">{{ __('Пока нет завершённых визитов.') }}</p>
                                @endforelse
                            </div>

                            <div>
                                <a href="{{ route('care.clients.show', $card['client']) }}"
                                   class="inline-flex w-full items-center justify-center rounded-full bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-primary-500">
                                    {{ __('Открыть профиль') }}
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection

