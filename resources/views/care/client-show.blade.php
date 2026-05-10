@extends('layouts.app')

@section('content')
    <div class="bg-slate-100 py-10">
        <div class="container mx-auto max-w-5xl px-4 space-y-8">
            <div>
                <a href="{{ route('care.dashboard') }}" class="text-sm font-semibold text-primary-600 hover:text-primary-500">
                    &larr; {{ __('Назад к списку подопечных') }}
                </a>
            </div>

            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-primary-500">{{ __('Профиль клиента') }}</p>
                        <h1 class="mt-2 text-3xl font-semibold text-slate-900">{{ $client->full_name }}</h1>
                        <p class="text-slate-500">
                            {{ $client->city ?? __('Город не указан') }} · {{ $client->phone ?? __('Телефон не указан') }}
                        </p>
                    </div>
                    @if ($canManage)
                        <span class="inline-flex rounded-full bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-600">
                            {{ __('У вас есть права на управление визитами') }}
                        </span>
                    @else
                        <span class="inline-flex rounded-full bg-slate-50 px-4 py-2 text-sm text-slate-500">
                            {{ __('Доступ только для просмотра') }}
                        </span>
                    @endif
                </div>

                <dl class="mt-6 grid gap-4 rounded-2xl bg-slate-50 p-4 text-sm text-slate-600 md:grid-cols-3">
                    <div>
                        <dt class="font-semibold text-slate-500">{{ __('Контакты') }}</dt>
                        <dd class="mt-1">
                            {{ $client->email ?? __('Email не указан') }}<br>
                            {{ $client->phone ?? __('Телефон не указан') }}
                        </dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">{{ __('Адрес') }}</dt>
                        <dd class="mt-1">
                            {{ $client->address_line ?? __('Адрес уточняется') }}<br>
                            {{ $client->postal_code }} {{ $client->city }}
                        </dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">{{ __('Особенности') }}</dt>
                        <dd class="mt-1">
                            {{ $client->mobility_notes ?? __('Без комментариев') }}
                        </dd>
                    </div>
                </dl>
            </div>

            <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                <h2 class="text-xl font-semibold text-slate-900">{{ __('Доверенные лица') }}</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-left text-sm text-slate-600">
                        <thead class="text-xs uppercase text-slate-400">
                        <tr>
                            <th class="pb-3 pr-4 font-semibold">{{ __('Имя') }}</th>
                            <th class="pb-3 pr-4 font-semibold">{{ __('Отношение') }}</th>
                            <th class="pb-3 pr-4 font-semibold">{{ __('Контакты') }}</th>
                            <th class="pb-3 pr-4 font-semibold">{{ __('Права') }}</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        @forelse ($client->trustedContacts as $contact)
                            <tr>
                                <td class="py-3 pr-4 font-semibold text-slate-800">{{ $contact->full_name }}</td>
                                <td class="py-3 pr-4">{{ $contact->relationship }}</td>
                                <td class="py-3 pr-4">
                                    {{ $contact->email }}<br>
                                    {{ $contact->phone }}
                                </td>
                                <td class="py-3 pr-4">
                                    <span class="inline-flex rounded-full bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-600">
                                        {{ $contact->can_view_reports ? __('Видит отчёты') : __('Без отчётов') }}
                                    </span>
                                    <span class="ml-2 inline-flex rounded-full bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-600">
                                        {{ $contact->can_manage_orders ? __('Управляет визитами') : __('Только просмотр') }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-4 text-center text-slate-500">
                                    {{ __('Доверенные лица не добавлены.') }}
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                <h2 class="text-xl font-semibold text-slate-900">{{ __('Планы заботы') }}</h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full text-left text-sm text-slate-600">
                        <thead class="text-xs uppercase text-slate-400">
                        <tr>
                            <th class="pb-3 pr-4 font-semibold">{{ __('Услуга') }}</th>
                            <th class="pb-3 pr-4 font-semibold">{{ __('Частота') }}</th>
                            <th class="pb-3 pr-4 font-semibold">{{ __('Время') }}</th>
                            <th class="pb-3 pr-4 font-semibold">{{ __('Статус') }}</th>
                            <th class="pb-3 pr-4 font-semibold">{{ __('Период') }}</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                        @forelse ($activePlans as $plan)
                            <tr>
                                <td class="py-3 pr-4 font-semibold text-slate-800">{{ $plan->careService?->name ?? __('Услуга') }}</td>
                                <td class="py-3 pr-4">{{ $plan->frequency }}</td>
                                <td class="py-3 pr-4">{{ optional($plan->time_of_day)->format('H:i') ?? '—' }}</td>
                                <td class="py-3 pr-4">{{ $plan->status }}</td>
                                <td class="py-3 pr-4">
                                    {{ optional($plan->starts_at)->format('d.m.Y') }} —
                                    {{ optional($plan->ends_at)->format('d.m.Y') ?? __('Без срока') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-4 text-center text-slate-500">
                                    {{ __('Активных планов нет.') }}
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <h2 class="text-xl font-semibold text-slate-900">{{ __('Ближайшие визиты') }}</h2>
                    <p class="text-sm text-slate-500">{{ __('Нажмите на визит, чтобы открыть детали и действия.') }}</p>
                </div>
                <div class="mt-4 space-y-3">
                    @forelse ($upcomingVisits as $visit)
                        <a href="{{ route('care.orders.show', $visit->order) }}"
                           class="flex flex-col gap-3 rounded-2xl border border-slate-100 bg-slate-50/80 p-4 transition hover:border-primary-200 hover:bg-white">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-slate-900">
                                        {{ optional($visit->scheduled_start_at)->format('d.m H:i') ?? __('Время уточняется') }}
                                    </p>
                                    <p class="text-sm text-slate-500">{{ $visit->careService?->name ?? __('Услуга') }}</p>
                                </div>
                                <span class="inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-500">
                                    {{ $visit->care_status ?? 'SCHEDULED' }}
                                </span>
                            </div>
                            @if ($visit->assignedHelper)
                                <p class="text-sm text-slate-500">
                                    {{ __('Помощник:') }} {{ $visit->assignedHelper->display_name }} · {{ $visit->assignedHelper->level }}
                                </p>
                            @endif
                        </a>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('Запланированных визитов нет.') }}</p>
                    @endforelse
                </div>
            </section>

            <section class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                <h2 class="text-xl font-semibold text-slate-900">{{ __('История визитов') }}</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($pastVisits as $visit)
                        <div class="rounded-2xl border border-slate-100 bg-slate-50/70 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3 text-sm text-slate-600">
                                <div>
                                    <p class="font-semibold text-slate-900">
                                        {{ optional($visit->scheduled_start_at)->format('d.m.Y') ?? '—' }}
                                    </p>
                                    <p>{{ $visit->careService?->name ?? __('Услуга') }}</p>
                                </div>
                                <div class="text-right">
                                    <p>{{ __('Статус:') }} {{ $visit->care_status ?? '—' }}</p>
                                    @if ($visit->visitReports->isNotEmpty())
                                        <p class="text-emerald-600">{{ __('Отчёт готов') }}</p>
                                    @else
                                        <p class="text-slate-400">{{ __('Отчёт в обработке') }}</p>
                                    @endif
                                </div>
                            </div>
                            <a href="{{ route('care.orders.show', $visit->order) }}"
                               class="mt-3 inline-flex rounded-full bg-white px-4 py-2 text-xs font-semibold text-primary-600 shadow hover:bg-primary-50">
                                {{ __('Открыть детали визита') }}
                            </a>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('История пока пуста.') }}</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endsection

