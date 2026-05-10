@extends('account.layout')

@section('title', 'Претензия #' . $claim->id . ' — Личный кабинет')
@section('header', 'Претензия #' . $claim->id)

@section('content')
@php
    $statusClasses = [
        'open' => 'bg-amber-100 text-amber-800',
        'in_progress' => 'bg-blue-100 text-blue-800',
        'resolved' => 'bg-emerald-100 text-emerald-800',
        'rejected' => 'bg-rose-100 text-rose-800',
    ];

    $severityClasses = [
        'critical' => 'bg-rose-100 text-rose-800',
        'high' => 'bg-orange-100 text-orange-800',
        'medium' => 'bg-amber-100 text-amber-800',
        'low' => 'bg-emerald-100 text-emerald-800',
    ];
@endphp

<div class="space-y-6">
    <section class="bg-white border border-slate-200 rounded-xl p-5 sm:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="space-y-2">
                <h2 class="text-xl font-semibold text-slate-900">{{ $claim->title }}</h2>
                <p class="text-sm text-slate-600">Создано: {{ $claim->created_at->format('d.m.Y H:i') }}</p>
                <p class="text-sm leading-relaxed text-slate-700">{{ $claim->description }}</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses[$claim->status] ?? 'bg-slate-100 text-slate-700' }}">
                    {{ ucfirst(str_replace('_', ' ', $claim->status)) }}
                </span>
                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $severityClasses[$claim->severity] ?? 'bg-slate-100 text-slate-700' }}">
                    {{ ucfirst($claim->severity) }}
                </span>
            </div>
        </div>

        <dl class="mt-5 grid grid-cols-1 gap-3 text-sm sm:grid-cols-2">
            @if($claim->order)
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Связанный заказ</dt>
                    <dd class="mt-1">
                        <a href="{{ route('account.orders.show', $claim->order) }}" class="font-medium text-primary-600 hover:text-primary-700">
                            #{{ $claim->order->id }}
                        </a>
                    </dd>
                </div>
            @endif

            @if($claim->assignedTo)
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Назначено</dt>
                    <dd class="mt-1 text-slate-800">{{ $claim->assignedTo->name }}</dd>
                </div>
            @endif
        </dl>
    </section>

    <section class="bg-white border border-slate-200 rounded-xl p-5 sm:p-6">
        <h3 class="text-base font-semibold text-slate-900">SLA и контроль сроков</h3>
        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Ответ до</p>
                <p class="mt-1 text-sm font-medium {{ $claim->sla_response_breached ? 'text-rose-600' : 'text-slate-800' }}">
                    {{ $claim->sla_response_due_at ? $claim->sla_response_due_at->format('d.m.Y H:i') : '—' }}
                </p>
                @if($claim->sla_response_breached)
                    <p class="mt-1 text-xs font-semibold text-rose-600">Срок ответа нарушен</p>
                @elseif($claim->responded_at)
                    <p class="mt-1 text-xs font-semibold text-emerald-700">Ответ предоставлен вовремя</p>
                @endif
            </div>

            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Решение до</p>
                <p class="mt-1 text-sm font-medium {{ $claim->sla_resolution_breached ? 'text-rose-600' : 'text-slate-800' }}">
                    {{ $claim->sla_resolution_due_at ? $claim->sla_resolution_due_at->format('d.m.Y H:i') : '—' }}
                </p>
                @if($claim->sla_resolution_breached)
                    <p class="mt-1 text-xs font-semibold text-rose-600">Срок решения нарушен</p>
                @elseif($claim->resolved_at)
                    <p class="mt-1 text-xs font-semibold text-emerald-700">Претензия закрыта</p>
                @endif
            </div>
        </div>
    </section>

    <section class="bg-white border border-slate-200 rounded-xl p-5 sm:p-6">
        <h3 class="text-base font-semibold text-slate-900">Переписка по претензии</h3>

        <div class="mt-4 max-h-96 space-y-3 overflow-y-auto rounded-lg border border-slate-200 bg-slate-50 p-3 sm:p-4">
            @forelse($claim->messages as $message)
                @php($isMine = $message->sender_id === auth()->id())
                <div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }}">
                    <article class="max-w-[85%] rounded-2xl px-3 py-2 text-sm sm:max-w-[70%] {{ $isMine ? 'bg-primary-100 text-primary-900' : 'bg-white text-slate-900 border border-slate-200' }}">
                        <header class="mb-1 text-[11px] text-slate-500">
                            <span>{{ $message->sender->name ?? 'Система' }}</span>
                            <span class="ml-2">{{ $message->created_at->format('d.m.Y H:i') }}</span>
                        </header>
                        <p class="whitespace-pre-wrap leading-relaxed">{{ $message->body }}</p>
                    </article>
                </div>
            @empty
                <p class="py-8 text-center text-sm text-slate-500">Пока нет сообщений по этой претензии.</p>
            @endforelse
        </div>

        <form action="{{ route('account.claims.messages.store', $claim) }}" method="POST" class="mt-4 space-y-3 border-t border-slate-200 pt-4">
            @csrf
            <label for="claim_message" class="block text-sm font-medium text-slate-700">Новое сообщение</label>
            <textarea
                id="claim_message"
                name="body"
                rows="4"
                class="w-full rounded-lg border-slate-300 focus:border-primary-500 focus:ring-primary-500"
                placeholder="Опишите вопрос или уточнение..."
                required
            ></textarea>
            <div class="flex justify-end">
                <button type="submit" class="inline-flex items-center rounded-lg bg-primary-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-primary-700">
                    Отправить
                </button>
            </div>
        </form>
    </section>
</div>
@endsection
