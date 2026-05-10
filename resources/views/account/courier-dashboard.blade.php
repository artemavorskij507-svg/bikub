@extends('account.layout')

@section('title', 'Курьерский кабинет - Личный кабинет')
@section('header', 'Курьерский кабинет')

@section('content')
<section x-data="courierDashboard()" class="space-y-6">
    <article class="card account-hero account-hero-warning">
        <div class="card-body account-hero-body">
            <div class="account-hero-content">
                <p class="account-hero-eyebrow">Курьерские операции</p>
                <h2 class="account-hero-title">
                    Статус на линии: <span x-text="isOnline ? 'Онлайн' : 'Оффлайн'"></span>
                </h2>
                <p class="account-hero-text">
                    Поддерживайте актуальный статус, чтобы получать задания в реальном времени и контролировать результат за день.
                </p>
            </div>
            <button
                type="button"
                class="btn"
                :class="isOnline ? 'btn-danger' : 'btn-primary'"
                @click="toggleStatus"
                x-text="isOnline ? 'Перейти в оффлайн' : 'Выйти на линию'"
            ></button>
        </div>
    </article>

    <section class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <article class="kpi-card">
            <header class="kpi-header"><p class="kpi-label">Заработок за сегодня</p></header>
            <p class="kpi-value">{{ number_format($todayEarnings, 0, ',', ' ') }} kr</p>
            <footer class="kpi-footer">Сумма по выполненным заданиям за день</footer>
        </article>
        <article class="kpi-card">
            <header class="kpi-header"><p class="kpi-label">Выполнено сегодня</p></header>
            <p class="kpi-value">{{ $todayCompleted }}</p>
            <footer class="kpi-footer">Количество выполненных заданий с 00:00</footer>
        </article>
        <article class="kpi-card">
            <header class="kpi-header"><p class="kpi-label">Выполнено за месяц</p></header>
            <p class="kpi-value">{{ $monthCompleted }}</p>
            <footer class="kpi-footer">Результат в текущем месяце</footer>
        </article>
    </section>

    <section class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2">
            <article class="card">
                <header class="card-header">
                    <h3 class="card-title">Текущее задание</h3>
                    <p class="card-subtitle">Текущий статус доставки и следующее действие.</p>
                </header>
                <div class="card-body">
                    @if($activeOrder)
                        <div class="grid gap-4">
                            <div class="account-split-header">
                                <div>
                                    <p class="account-item-title text-lg">
                                        Заказ #{{ $activeOrder->order_number }}
                                    </p>
                                    <p class="account-item-subtitle mt-1">
                                        @if($activeOrder->address)
                                            {{ $activeOrder->address->address_line_1 }}, {{ $activeOrder->address->city }}
                                        @else
                                            Адрес не указан
                                        @endif
                                    </p>
                                </div>
                                <span class="status-pill status-pill-warning uppercase">
                                    {{ $activeOrder->status }}
                                </span>
                            </div>

                            @if($activeOrder->deliveryOrder)
                                <div class="account-meta-grid">
                                    <div class="account-meta-card">
                                        <p class="account-meta-label">Вес</p>
                                        <p class="account-meta-value">
                                            {{ $activeOrder->deliveryOrder->weight_kg ?? 'N/A' }} kg
                                        </p>
                                    </div>
                                    <div class="account-meta-card">
                                        <p class="account-meta-label">Дистанция</p>
                                        <p class="account-meta-value">
                                            {{ $activeOrder->deliveryOrder->distance_km ?? 'N/A' }} km
                                        </p>
                                    </div>
                                </div>
                            @endif

                            <div class="account-hero-actions">
                                <a href="{{ route('account.orders.show', $activeOrder) }}" class="btn btn-primary">Открыть задание</a>
                                <a href="{{ route('account.orders.index') }}" class="btn btn-secondary">История заказов</a>
                            </div>
                        </div>
                    @else
                        <div class="card-empty">
                            <div class="card-empty-content">
                                <svg class="card-empty-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.6"/>
                                    <path d="M12 8v4l3 3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                </svg>
                                <h4 class="card-empty-title">Нет активного задания</h4>
                                <p class="card-empty-text">Оставайтесь онлайн, чтобы получить следующую задачу автоматически.</p>
                            </div>
                        </div>
                    @endif
                </div>
            </article>
        </div>

        <div class="space-y-6">
            <article class="card">
                <header class="card-header">
                    <h3 class="card-title">Быстрые действия</h3>
                    <p class="card-subtitle">Самые нужные разделы для ежедневной работы.</p>
                </header>
                <div class="card-body grid gap-3">
                    <a href="{{ route('account.orders.index') }}" class="btn btn-secondary">История заказов</a>
                    <a href="{{ route('account.profile.edit') }}" class="btn btn-secondary">Настройки профиля</a>
                    <a href="{{ route('account.notifications.index') }}" class="btn btn-secondary">Уведомления</a>
                </div>
            </article>

            <article class="card">
                <header class="card-header">
                    <h3 class="card-title">Подсказки по работе</h3>
                    <p class="card-subtitle">Балансируйте скорость и качество обслуживания.</p>
                </header>
                <div class="card-body">
                    <ul class="account-note-list">
                        <li>Проверяйте маршрут и детали заказа до выезда.</li>
                        <li>Включайте статус онлайн только когда готовы к приему задач.</li>
                        <li>Обновляйте статус задания сразу после выполнения.</li>
                    </ul>
                </div>
            </article>
        </div>
    </section>
</section>
@endsection

@push('scripts')
<script>
    function courierDashboard() {
        return {
            isOnline: @json($isOnline),
            toggleStatus() {
                this.isOnline = !this.isOnline;

                fetch('/api/courier/status', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        online: this.isOnline
                    })
                }).catch(() => {
                    this.isOnline = !this.isOnline;
                });
            }
        };
    }
</script>
@endpush
