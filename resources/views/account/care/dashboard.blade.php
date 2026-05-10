@extends('account.layout')

@section('title', 'Социальная помощь — Личный кабинет')
@section('header', 'Социальная помощь')

@section('content')
<div class="space-y-6">
    @if($clients->count() > 1)
        <section class="bg-white border border-slate-200 rounded-xl p-5 sm:p-6">
            <h2 class="text-lg font-semibold text-slate-900">Мои подопечные</h2>
            <p class="mt-1 text-sm text-slate-600">Список профилей, к которым у вас есть доступ.</p>
            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                @foreach($clients as $client)
                    <article class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="font-medium text-slate-900">{{ $client->full_name }}</p>
                        @if($client->phone)
                            <p class="mt-1 text-sm text-slate-600">{{ $client->phone }}</p>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <section class="bg-white border border-slate-200 rounded-xl">
        <header class="border-b border-slate-200 px-5 py-4 sm:px-6">
            <h2 class="text-lg font-semibold text-slate-900">Ближайшие визиты</h2>
        </header>
        <div class="p-5 sm:p-6">
            @if($upcomingVisits->isEmpty())
                <p class="py-8 text-center text-sm text-slate-500">Запланированных визитов пока нет.</p>
            @else
                <div class="space-y-3">
                    @foreach($upcomingVisits as $visit)
                        <article class="rounded-lg border border-slate-200 p-4 hover:bg-slate-50">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <h3 class="font-semibold text-slate-900">{{ $visit->careService?->name ?? 'Социальный визит' }}</h3>
                                    @if($visit->clientProfile)
                                        <p class="mt-1 text-sm text-slate-600">Для: {{ $visit->clientProfile->full_name }}</p>
                                    @endif
                                    <p class="mt-1 text-xs text-slate-500">{{ $visit->scheduled_start_at?->format('d.m.Y H:i') }}</p>
                                    @if($visit->assignedHelper)
                                        <p class="mt-1 text-xs text-slate-500">Помощник: {{ $visit->assignedHelper->user->name ?? 'Не назначен' }}</p>
                                    @endif
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold
                                        @if($visit->care_status === 'COMPLETED') bg-emerald-100 text-emerald-800
                                        @elseif(in_array($visit->care_status, ['CANCELLED', 'CANCELLED_BY_CLIENT', 'CANCELLED_BY_OPERATOR', 'CANCELLED_BY_TRUSTED_CONTACT'])) bg-rose-100 text-rose-800
                                        @else bg-blue-100 text-blue-800
                                        @endif">
                                        {{ $visit->care_status }}
                                    </span>
                                    <a href="{{ route('account.care.visit.show', $visit->order) }}" class="text-sm font-medium text-primary-600 hover:text-primary-700">
                                        Подробнее
                                    </a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section class="bg-white border border-slate-200 rounded-xl">
        <header class="border-b border-slate-200 px-5 py-4 sm:px-6">
            <h2 class="text-lg font-semibold text-slate-900">Последние отчеты</h2>
        </header>
        <div class="p-5 sm:p-6">
            @if($recentReports->isEmpty())
                <p class="py-8 text-center text-sm text-slate-500">Отчетов пока нет.</p>
            @else
                <div class="space-y-3">
                    @foreach($recentReports as $report)
                        <article class="rounded-lg border border-slate-200 p-4">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <p class="text-sm font-medium text-slate-900">{{ $report->created_at->format('d.m.Y H:i') }}</p>
                                @if($report->helperProfile)
                                    <p class="text-sm text-slate-600">Помощник: {{ $report->helperProfile->user->name ?? 'Неизвестно' }}</p>
                                @endif
                            </div>
                            @if($report->careOrderDetails && $report->careOrderDetails->order)
                                <p class="mt-2 text-sm text-slate-600">Заказ #{{ $report->careOrderDetails->order->order_number }}</p>
                            @endif
                            @if($report->summary)
                                <p class="mt-2 whitespace-pre-wrap text-sm text-slate-700">{{ $report->summary }}</p>
                            @endif
                            @if($report->careOrderDetails && $report->careOrderDetails->order)
                                <a href="{{ route('account.care.visit.show', $report->careOrderDetails->order) }}" class="mt-3 inline-flex text-sm font-medium text-primary-600 hover:text-primary-700">
                                    Перейти к визиту
                                </a>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</div>
@endsection
