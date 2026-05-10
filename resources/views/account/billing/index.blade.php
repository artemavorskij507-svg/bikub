@extends('account.layout')

@section('title', 'Финансы и платежи')
@section('header', 'Центр платежей')

@section('content')
<div class="space-y-6">
    <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="bg-white border border-slate-200 rounded-xl p-4">
            <p class="text-sm text-slate-500">Всего оплачено</p>
            <p class="mt-1 text-2xl font-semibold text-slate-900">{{ number_format(($summary->total_charged ?? 0) / 100, 2, ',', ' ') }} NOK</p>
        </article>
        <article class="bg-white border border-slate-200 rounded-xl p-4">
            <p class="text-sm text-slate-500">Возвраты</p>
            <p class="mt-1 text-2xl font-semibold text-slate-900">{{ number_format(($summary->total_refunded ?? 0) / 100, 2, ',', ' ') }} NOK</p>
        </article>
        <article class="bg-white border border-slate-200 rounded-xl p-4">
            <p class="text-sm text-slate-500">Чаевые</p>
            <p class="mt-1 text-2xl font-semibold text-slate-900">{{ number_format(($summary->total_tips ?? 0) / 100, 2, ',', ' ') }} NOK</p>
        </article>
        <article class="bg-white border border-slate-200 rounded-xl p-4">
            <p class="text-sm text-slate-500">Итоговый расход</p>
            <p class="mt-1 text-2xl font-semibold text-slate-900">{{ number_format(($summary->net_total ?? 0) / 100, 2, ',', ' ') }} NOK</p>
        </article>
    </section>

    <section class="bg-white border border-slate-200 rounded-xl">
        <header class="flex flex-col gap-2 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <h2 class="text-lg font-semibold text-slate-900">Недавние транзакции</h2>
            <a href="{{ route('account.billing.transactions') }}" class="text-sm font-medium text-primary-600 hover:text-primary-700">
                Все транзакции
            </a>
        </header>

        <div class="divide-y divide-slate-100">
            @forelse($latestTransactions as $transaction)
                <article class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div>
                        <p class="font-medium text-slate-900">{{ ucfirst($transaction->type) }} — {{ $transaction->label ?? 'Транзакция' }}</p>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ optional($transaction->processed_at)->format('d.m.Y H:i') }} ·
                            {{ $transaction->provider ?? 'internal' }} ·
                            {{ $transaction->status }}
                        </p>
                    </div>
                    <div class="text-left sm:text-right">
                        <p class="text-lg font-semibold {{ $transaction->amount_minor < 0 ? 'text-rose-600' : 'text-slate-900' }}">
                            {{ number_format($transaction->amount_minor / 100, 2, ',', ' ') }} NOK
                        </p>
                        @if($transaction->order)
                            <a href="{{ route('account.orders.show', $transaction->order) }}" class="text-xs font-medium text-primary-600 hover:text-primary-700">
                                Заказ #{{ $transaction->order_id }}
                            </a>
                        @endif
                    </div>
                </article>
            @empty
                <div class="px-6 py-10 text-center text-sm text-slate-500">Транзакций пока нет.</div>
            @endforelse
        </div>
    </section>
</div>
@endsection
