@extends('account.layout')

@section('title', 'Транзакции')
@section('header', 'История платежей')

@section('content')
<div class="space-y-6">
    <section class="bg-white border border-slate-200 rounded-xl p-4 sm:p-5">
        <form method="GET" class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-[1fr,1fr,auto,auto]" aria-label="Фильтр транзакций">
            <div>
                <label for="tx-type" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Тип операции</label>
                <select id="tx-type" name="type" class="mt-1 rounded-lg border-slate-300 text-sm">
                    <option value="">Все типы</option>
                    @foreach(['charge' => 'Оплата', 'refund' => 'Возврат', 'tip' => 'Чаевые', 'subsidy' => 'Субсидия', 'adjustment' => 'Коррекция'] as $value => $label)
                        <option value="{{ $value }}" @selected($currentType === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="tx-service-type" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Услуга</label>
                <select id="tx-service-type" name="service_type" class="mt-1 rounded-lg border-slate-300 text-sm">
                    <option value="">Все услуги</option>
                    @foreach(\App\Enums\ServiceType::cases() as $serviceType)
                        <option value="{{ $serviceType->value }}" @selected($currentServiceType === $serviceType->value)>
                            {{ $serviceType->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end">
                <x-primary-button class="w-full justify-center sm:w-auto">Фильтровать</x-primary-button>
            </div>

            <div class="flex items-end">
                <a href="{{ route('account.billing.transactions') }}" class="inline-flex min-h-[42px] items-center justify-center rounded-lg border border-slate-300 px-4 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                    Сбросить
                </a>
            </div>
        </form>
    </section>

    <section class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm" aria-label="Таблица транзакций">
                <caption class="sr-only">Payment transactions history table</caption>
                <thead class="bg-slate-50">
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <th scope="col" class="px-4 py-3">Дата</th>
                        <th scope="col" class="px-4 py-3">Тип</th>
                        <th scope="col" class="px-4 py-3">Сумма</th>
                        <th scope="col" class="px-4 py-3">Статус</th>
                        <th scope="col" class="px-4 py-3">Услуга</th>
                        <th scope="col" class="px-4 py-3">Чек / заказ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($transactions as $transaction)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-4 py-3 text-slate-700">{{ optional($transaction->processed_at)->format('d.m.Y H:i') }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ ucfirst($transaction->type) }}</td>
                            <td class="px-4 py-3 font-semibold {{ $transaction->amount_minor < 0 ? 'text-rose-600' : 'text-slate-900' }}">
                                {{ number_format($transaction->amount_minor / 100, 2, ',', ' ') }} NOK
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $transaction->status }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $transaction->order?->service_type ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">
                                <div class="flex flex-col gap-1">
                                    @if(!empty($transaction->meta['receipt_url']))
                                        <a href="{{ $transaction->meta['receipt_url'] }}" class="text-primary-600 hover:text-primary-700" target="_blank" rel="noopener">
                                            Чек
                                        </a>
                                    @elseif($transaction->order?->receipt_url)
                                        <a href="{{ $transaction->order->receipt_url }}" class="text-primary-600 hover:text-primary-700" target="_blank" rel="noopener">
                                            Чек
                                        </a>
                                    @endif

                                    @if($transaction->order_id)
                                        <a href="{{ route('account.orders.show', $transaction->order_id) }}" class="text-primary-600 hover:text-primary-700">
                                            Заказ #{{ $transaction->order_id }}
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">Транзакций по выбранным фильтрам не найдено.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div>
        {{ $transactions->links() }}
    </div>
</div>
@endsection

