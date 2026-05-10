@extends('account.layout')

@section('title', 'Мои заказы — Личный кабинет')
@section('header', 'Мои заказы')

@section('content')
@php
    $formatPrice = function ($value, $currency = 'NOK') {
        if ($value === null || $value === '') {
            return '—';
        }

        return number_format((float) $value, 0, '.', ' ') . ' ' . $currency;
    };
@endphp

<x-bikube.os-shell>
    <x-bikube.page-header
        eyebrow="BiKuBe OS / Account"
        title="Мои заказы"
        subtitle="Customer order center with tracker, repeat and support actions."
        badge="Wave 3B UI Core v2"
        :refresh-url="route('account.orders.index')"
        :open-url="route('account.new-order.index')"
        open-label="Новый заказ"
        :chips="['Order tracker', 'Repeat flow', 'Support access']"
    >
        <x-slot:actions>
            <a href="{{ route('account.claims.index') }}" class="bikube-os-btn bikube-os-btn-soft">Поддержка</a>
        </x-slot:actions>
    </x-bikube.page-header>

    @if(isset($activeClient) && $activeClient)
        <x-bikube.action-card title="Активный профиль клиента" subtitle="Контекст отображения заказов">
            <p class="text-sm text-slate-700">Сейчас показываются заказы клиента: <strong>{{ $activeClient->full_name }}</strong>.</p>
        </x-bikube.action-card>
    @endif

    <section class="bikube-os-grid-3">
        <x-bikube.kpi-card label="Заказы на странице" :value="$orders->total()" hint="С учетом выбранных фильтров" tone="slate" />
        <x-bikube.kpi-card label="Активные" :value="$activeOrderCount ?? 0" hint="Ожидают выполнения" tone="blue" />
        <x-bikube.kpi-card label="История" :value="$historyOrderCount ?? 0" hint="Закрытые/архивные статусы" tone="emerald" />
    </section>

    <x-bikube.action-card title="Фильтры заказов" subtitle="Выберите тип услуги и статус">
        <form method="GET" action="{{ route('account.orders.index') }}" class="grid gap-4 md:grid-cols-3" novalidate>
            <div>
                <label for="order-filter-type" class="bikube-os-info-label">Тип услуги</label>
                <select id="order-filter-type" name="type" class="mt-2 rounded-lg border-slate-300">
                    <option value="">Все типы</option>
                    @foreach($serviceTypes as $value => $label)
                        <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="order-filter-status" class="bikube-os-info-label">Статус</label>
                <select id="order-filter-status" name="status" class="mt-2 rounded-lg border-slate-300">
                    <option value="">Все статусы</option>
                    @foreach($statuses as $key => $label)
                        <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="bikube-os-btn bikube-os-btn-primary">Применить</button>
                @if(request()->hasAny(['type', 'status']))
                    <a href="{{ route('account.orders.index') }}" class="bikube-os-btn bikube-os-btn-soft">Сбросить</a>
                @endif
            </div>
        </form>
    </x-bikube.action-card>

    <x-bikube.action-card title="Заказы" subtitle="Активные и завершенные заявки с быстрыми действиями">
        @if($orders->isEmpty())
            <x-bikube.empty-state
                title="Заказы не найдены"
                :message="request()->hasAny(['type', 'status']) ? 'По выбранным фильтрам ничего не найдено.' : 'У вас пока нет заказов. Оформите первый заказ и вернитесь сюда для трекинга.'"
                action-label="Создать заказ"
                :action-href="route('account.new-order.index')"
            />
        @else
            <div class="space-y-3">
                @foreach($orders as $order)
                    <x-bikube.order-card
                        :title="'Заказ #'.$order['order_number']"
                        :meta="'Создан: '.($order['created_at']?->format('d.m.Y H:i') ?? '—').($order['scheduled_at'] ? ' · Запланирован: '.$order['scheduled_at']->format('d.m.Y H:i') : '')"
                        :status="$order['status_label'] ?? '—'"
                        :payment="$order['payment_label'] ?? 'Оплата —'"
                        :priority="$order['priority'] ?? null"
                    >
                        <div class="bikube-os-info-grid">
                            <div class="bikube-os-info">
                                <p class="bikube-os-info-label">Услуга</p>
                                <p class="bikube-os-info-value">
                                    {{ $order['service_label'] ?? ($order['title'] ?? 'Услуга') }}
                                    @if(!empty($order['scenario_key']))
                                        · {{ $order['scenario_key'] }}
                                    @endif
                                </p>
                            </div>
                            <div class="bikube-os-info">
                                <p class="bikube-os-info-label">Стоимость</p>
                                <p class="bikube-os-info-value">{{ $formatPrice($order['price_value'] ?? null, $order['currency'] ?? 'NOK') }}</p>
                            </div>
                        </div>

                        <div class="mt-3 bikube-os-actions">
                            <a href="{{ route('account.orders.track', $order['id']) }}" class="bikube-os-btn bikube-os-btn-primary">Трекер</a>
                            <a href="{{ route('account.orders.show', $order['id']) }}" class="bikube-os-btn bikube-os-btn-soft">Открыть заказ</a>
                            <a href="{{ route('account.new-order.index', ['repeat' => $order['id']]) }}" class="bikube-os-btn bikube-os-btn-soft">Повторить заказ</a>
                            <a href="{{ route('account.orders.claim.create', $order['id']) }}" class="bikube-os-btn bikube-os-btn-soft">Поддержка</a>
                        </div>
                    </x-bikube.order-card>
                @endforeach
            </div>
        @endif

        @if($orders->hasPages())
            <div class="mt-4">
                {{ $orders->links() }}
            </div>
        @endif
    </x-bikube.action-card>
</x-bikube.os-shell>
@endsection
