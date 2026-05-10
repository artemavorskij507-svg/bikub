@extends('account.layout')

@section('title', 'Визит #' . $order->order_number . ' — Личный кабинет')
@section('header', 'Визит #' . $order->order_number)

@section('content')
@php
    $details = $order->careDetails;
@endphp

<div class="space-y-6">
    <section class="bg-white border border-slate-200 rounded-xl p-5 sm:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">{{ $details->careService?->name ?? 'Социальный визит' }}</h2>
                <p class="mt-2 text-sm text-slate-600">Заказ: <span class="font-medium text-slate-800">#{{ $order->order_number }}</span></p>
                @if($details->scheduled_start_at)
                    <p class="mt-1 text-sm text-slate-600">Начало: {{ $details->scheduled_start_at->format('d.m.Y H:i') }}</p>
                @endif
                @if($details->scheduled_end_at)
                    <p class="mt-1 text-sm text-slate-600">Окончание: {{ $details->scheduled_end_at->format('d.m.Y H:i') }}</p>
                @endif
            </div>

            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold
                @if($details->care_status === 'COMPLETED') bg-emerald-100 text-emerald-800
                @elseif(in_array($details->care_status, ['CANCELLED', 'CANCELLED_BY_CLIENT', 'CANCELLED_BY_OPERATOR', 'CANCELLED_BY_TRUSTED_CONTACT'])) bg-rose-100 text-rose-800
                @else bg-blue-100 text-blue-800
                @endif">
                {{ $details->care_status }}
            </span>
        </div>
    </section>

    @if($details->clientProfile)
        <section class="bg-white border border-slate-200 rounded-xl p-5 sm:p-6">
            <h3 class="text-lg font-semibold text-slate-900">Клиент</h3>
            <div class="mt-3 space-y-2 text-sm text-slate-700">
                <p class="font-medium">{{ $details->clientProfile->full_name }}</p>
                @if($details->clientProfile->phone)
                    <p>Телефон: {{ $details->clientProfile->phone }}</p>
                @endif
                @if($details->clientProfile->address)
                    <p>Адрес: {{ $details->clientProfile->address->formatted_address ?? $details->clientProfile->address->street_address }}</p>
                @endif
            </div>
        </section>
    @endif

    @if($details->assignedHelper)
        <section class="bg-white border border-slate-200 rounded-xl p-5 sm:p-6">
            <h3 class="text-lg font-semibold text-slate-900">Помощник</h3>
            <div class="mt-3 space-y-2 text-sm text-slate-700">
                <p class="font-medium">{{ $details->assignedHelper->user->name ?? 'Не назначен' }}</p>
                @if($details->assignedHelper->user->phone)
                    <p>Телефон: {{ $details->assignedHelper->user->phone }}</p>
                @endif
            </div>
        </section>
    @endif

    @if($details->visitReports->isNotEmpty())
        <section class="bg-white border border-slate-200 rounded-xl p-5 sm:p-6">
            <h3 class="text-lg font-semibold text-slate-900">Отчеты о визите</h3>
            <div class="mt-4 space-y-3">
                @foreach($details->visitReports as $report)
                    <article class="rounded-lg border border-slate-200 p-4">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-sm font-medium text-slate-900">{{ $report->created_at->format('d.m.Y H:i') }}</p>
                            @if($report->helperProfile)
                                <p class="text-sm text-slate-600">Помощник: {{ $report->helperProfile->user->name ?? 'Неизвестно' }}</p>
                            @endif
                        </div>
                        @if($report->summary)
                            <p class="mt-2 whitespace-pre-wrap text-sm text-slate-700">{{ $report->summary }}</p>
                        @endif
                        @if($report->notes)
                            <p class="mt-2 text-sm italic text-slate-600">{{ $report->notes }}</p>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    @if($order->parentOrder)
        <section class="bg-white border border-slate-200 rounded-xl p-5 sm:p-6">
            <h3 class="text-lg font-semibold text-slate-900">Связанный заказ</h3>
            <a href="{{ route('account.orders.show', $order->parentOrder) }}" class="mt-3 inline-flex text-sm font-medium text-primary-600 hover:text-primary-700">
                Заказ #{{ $order->parentOrder->order_number }}
            </a>
        </section>
    @endif

    @if($details->notes)
        <section class="bg-white border border-slate-200 rounded-xl p-5 sm:p-6">
            <h3 class="text-lg font-semibold text-slate-900">Примечания</h3>
            <p class="mt-3 whitespace-pre-wrap text-sm leading-relaxed text-slate-700">{{ $details->notes }}</p>
        </section>
    @endif
</div>
@endsection
