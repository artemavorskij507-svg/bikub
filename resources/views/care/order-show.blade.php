@extends('layouts.app')

@section('content')
    <div class="bg-slate-100 py-10">
        <div class="container mx-auto max-w-4xl px-4 space-y-8">
            <div class="flex items-center gap-3 text-sm">
                <a href="{{ route('care.clients.show', $careDetails->clientProfile) }}"
                   class="font-semibold text-primary-600 hover:text-primary-500">
                    &larr; {{ __('К профилю :name', ['name' => $careDetails->clientProfile->full_name]) }}
                </a>
                <span class="text-slate-400">/</span>
                <span class="text-slate-500">{{ __('Визит #') }}{{ $order->order_number }}</span>
            </div>

            @if ($errors->any())
                <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('status'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-primary-500">{{ __('Визит') }}</p>
                        <h1 class="text-2xl font-semibold text-slate-900">
                            {{ $careDetails->careService?->name ?? __('Социальный визит') }}
                        </h1>
                        <p class="text-slate-500">
                            {{ __('Дата:') }} {{ optional($careDetails->scheduled_start_at)->format('d.m.Y H:i') ?? __('Уточняется') }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs uppercase tracking-wide text-slate-400">{{ __('Статус визита') }}</p>
                        <span class="mt-1 inline-flex rounded-full bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700">
                            {{ $careDetails->care_status ?? 'SCHEDULED' }}
                        </span>
                    </div>
                </div>

                <dl class="mt-6 grid gap-4 rounded-2xl bg-slate-50 p-4 text-sm text-slate-600 md:grid-cols-3">
                    <div>
                        <dt class="font-semibold text-slate-500">{{ __('Клиент') }}</dt>
                        <dd class="mt-1">{{ $careDetails->clientProfile->full_name }}</dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">{{ __('Назначенный помощник') }}</dt>
                        <dd class="mt-1">
                            @if ($careDetails->assignedHelper)
                                {{ $careDetails->assignedHelper->display_name }} · {{ $careDetails->assignedHelper->level }}
                            @else
                                {{ __('Назначение в работе') }}
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="font-semibold text-slate-500">{{ __('Контакты для связи') }}</dt>
                        <dd class="mt-1">
                            {{ $careDetails->trustedContact?->full_name ?? $careDetails->clientProfile->full_name }}<br>
                            {{ $careDetails->trustedContact?->phone ?? $careDetails->clientProfile->phone }}
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                <h2 class="text-xl font-semibold text-slate-900">{{ __('Отчёт о визите') }}</h2>
                @if ($visitReport)
                    <dl class="mt-4 space-y-3 text-sm text-slate-600">
                        <div>
                            <dt class="font-semibold text-slate-500">{{ __('Фактическое время') }}</dt>
                            <dd class="mt-1">
                                {{ optional($visitReport->started_at)->format('d.m H:i') }} —
                                {{ optional($visitReport->ended_at)->format('d.m H:i') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-slate-500">{{ __('Коротко о визите') }}</dt>
                            <dd class="mt-1 whitespace-pre-line">{{ $visitReport->summary }}</dd>
                        </div>
                        @if ($visitReport->client_mood)
                            <div>
                                <dt class="font-semibold text-slate-500">{{ __('Настроение клиента') }}</dt>
                                <dd class="mt-1">{{ $visitReport->client_mood }}</dd>
                            </div>
                        @endif
                        @if ($visitReport->issues_noted)
                            <div>
                                <dt class="font-semibold text-slate-500">{{ __('Замечания') }}</dt>
                                <dd class="mt-1 whitespace-pre-line">{{ $visitReport->issues_noted }}</dd>
                            </div>
                        @endif
                        @if ($visitReport->followup_recommended)
                            <div>
                                <dt class="font-semibold text-slate-500">{{ __('Рекомендации по последующим действиям') }}</dt>
                                <dd class="mt-1 whitespace-pre-line">{{ $visitReport->followup_notes }}</dd>
                            </div>
                        @endif
                    </dl>
                @else
                    <p class="mt-3 text-sm text-slate-500">
                        {{ __('Отчёт появится здесь после завершения визита.') }}
                    </p>
                @endif
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                @if ($canCancel)
                    <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                        <h3 class="text-lg font-semibold text-slate-900">{{ __('Отменить визит') }}</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ __('Отмена доступна за :hours часов до начала. Координатор получит уведомление сразу.', ['hours' => 2]) }}
                        </p>
                        <form method="POST" action="{{ route('care.orders.cancel', $order) }}" class="mt-4 space-y-3">
                            @csrf
                            <label class="text-sm font-medium text-slate-700" for="cancel-reason">{{ __('Причина (необязательно)') }}</label>
                            <textarea id="cancel-reason" name="reason" rows="3"
                                      class="w-full rounded-2xl border border-slate-200 px-3 py-2 text-sm text-slate-800 focus:border-primary-500 focus:ring-primary-500">{{ old('reason') }}</textarea>
                            <button type="submit"
                                    class="w-full rounded-full bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-red-500">
                                {{ __('Отменить визит') }}
                            </button>
                        </form>
                    </div>
                @endif

                @if ($canRequestReschedule)
                    <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                        <h3 class="text-lg font-semibold text-slate-900">{{ __('Запросить перенос') }}</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ __('Мы передадим координатору желаемую дату и время. Он подтвердит перенос или предложит альтернативу.') }}
                        </p>
                        <form method="POST" action="{{ route('care.orders.request-reschedule', $order) }}" class="mt-4 space-y-3">
                            @csrf
                            <div>
                                <label for="new_date" class="text-sm font-medium text-slate-700">{{ __('Желаемая дата') }}</label>
                                <input type="date" id="new_date" name="new_date" value="{{ old('new_date') }}"
                                       class="mt-1 w-full rounded-2xl border border-slate-200 px-3 py-2 text-sm text-slate-800 focus:border-primary-500 focus:ring-primary-500" required>
                            </div>
                            <div>
                                <label for="new_time" class="text-sm font-medium text-slate-700">{{ __('Желаемое время') }}</label>
                                <input type="time" id="new_time" name="new_time" value="{{ old('new_time') }}"
                                       class="mt-1 w-full rounded-2xl border border-slate-200 px-3 py-2 text-sm text-slate-800 focus:border-primary-500 focus:ring-primary-500" required>
                            </div>
                            <div>
                                <label for="reschedule-reason" class="text-sm font-medium text-slate-700">{{ __('Комментарий') }}</label>
                                <textarea id="reschedule-reason" name="reason" rows="3"
                                          class="mt-1 w-full rounded-2xl border border-slate-200 px-3 py-2 text-sm text-slate-800 focus:border-primary-500 focus:ring-primary-500"
                                          placeholder="{{ __('Например: поездка к врачу...') }}">{{ old('reason') }}</textarea>
                            </div>
                            <button type="submit"
                                    class="w-full rounded-full bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-primary-500">
                                {{ __('Отправить запрос') }}
                            </button>
                        </form>
                    </div>
                @endif
            </div>

            @if ($changeRequests->isNotEmpty())
                <div class="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-100">
                    <h3 class="text-lg font-semibold text-slate-900">{{ __('История запросов на перенос') }}</h3>
                    <div class="mt-4 space-y-2 text-sm text-slate-600">
                        @foreach ($changeRequests as $requestLog)
                            <div class="rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3">
                                <p class="font-semibold text-slate-900">
                                    {{ __('Запрошено:') }}
                                    {{ optional($requestLog->requested_new_start_at)->format('d.m H:i') }}
                                    ({{ __('статус') }}: {{ $requestLog->status }})
                                </p>
                                <p class="text-slate-500">
                                    {{ __('Отправлено:') }} {{ optional($requestLog->created_at)->format('d.m.Y H:i') }}
                                </p>
                                @if ($requestLog->reason)
                                    <p class="mt-2 whitespace-pre-line text-slate-600">{{ $requestLog->reason }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

