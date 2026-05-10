<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Личный кабинет') - GLF Bikube</title>

    <link rel="stylesheet" href="{{ asset('design-system/design-system.css') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .account-shell {
            min-height: 100vh;
            background: linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%);
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .sidebar-brand-link {
            text-decoration: none;
            color: var(--color-text-primary);
            font-size: 1.05rem;
            font-weight: var(--font-weight-bold);
            letter-spacing: var(--letter-spacing-wide);
        }

        .account-sidebar-close {
            display: none;
            align-items: center;
            justify-content: center;
            width: 2.25rem;
            height: 2.25rem;
            border: 0;
            border-radius: var(--radius-md);
            background: transparent;
            color: var(--color-text-secondary);
            cursor: pointer;
        }

        .account-sidebar-close:hover {
            background-color: var(--color-bg-secondary);
            color: var(--color-text-primary);
        }

        .account-overlay {
            display: none;
        }

        .account-main {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .account-main-inner {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            gap: 1rem;
        }

        .account-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem 1.25rem;
            border: 1px solid var(--color-border-default);
            border-radius: var(--radius-xl);
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(8px);
            box-shadow: var(--shadow-sm);
        }

        .account-heading {
            margin: 0;
            font-size: clamp(1.125rem, 2vw, 1.65rem);
            font-weight: var(--font-weight-bold);
            line-height: 1.2;
            color: var(--color-text-primary);
        }

        .account-subheading {
            margin: 0.35rem 0 0;
            color: var(--color-text-secondary);
            font-size: var(--font-size-sm);
        }

        .account-user-actions {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
        }

        .account-user-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 0.75rem;
            border-radius: var(--radius-full);
            background-color: var(--color-bg-secondary);
            border: 1px solid var(--color-border-default);
            color: var(--color-text-secondary);
            font-size: var(--font-size-sm);
            font-weight: var(--font-weight-medium);
        }

        .account-content {
            display: grid;
            gap: 1rem;
        }

        .account-content > * {
            animation: account-fade-up 220ms ease-out;
        }

        .account-content .bg-white {
            border: 1px solid var(--color-border-default);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-sm);
        }

        .account-content .border,
        .account-content .border-slate-200,
        .account-content .border-slate-300 {
            border-color: var(--color-border-default) !important;
        }

        .account-content .rounded-xl {
            border-radius: var(--radius-xl) !important;
        }

        .account-content .rounded-lg {
            border-radius: var(--radius-lg) !important;
        }

        .account-content .rounded-md {
            border-radius: var(--radius-md) !important;
        }

        .account-content table {
            width: 100%;
            border-collapse: collapse;
        }

        .account-content table thead th {
            text-align: left;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            border-bottom: 1px solid #e2e8f0;
            padding: 0.65rem 0.75rem;
        }

        .account-content table tbody td {
            border-bottom: 1px solid #f1f5f9;
            padding: 0.75rem;
            color: #0f172a;
            vertical-align: top;
        }

        .account-content input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]),
        .account-content select,
        .account-content textarea {
            width: 100%;
            min-height: 2.65rem;
            border: 1px solid var(--color-border-default);
            border-radius: var(--radius-lg);
            background: #fff;
            color: var(--color-text-primary);
            padding: 0.6rem 0.75rem;
            transition: border-color 160ms ease, box-shadow 160ms ease;
        }

        .account-content input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]):focus,
        .account-content select:focus,
        .account-content textarea:focus {
            border-color: var(--color-border-focus);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.18);
            outline: none;
        }

        .account-content .inline-flex,
        .account-content .btn,
        .account-topbar .btn {
            transition: transform 120ms ease, box-shadow 160ms ease;
        }

        .account-content .inline-flex:hover,
        .account-content .btn:hover,
        .account-topbar .btn:hover {
            transform: translateY(-1px);
        }

        .account-hero {
            overflow: hidden;
        }

        .account-hero-primary {
            border-color: #bfdbfe;
            background: linear-gradient(135deg, #eff6ff 0%, #ffffff 52%, #eef2ff 100%);
        }

        .account-hero-warning {
            border-color: #fde68a;
            background: linear-gradient(135deg, #fffbeb 0%, #ffffff 58%, #fff7ed 100%);
        }

        .account-hero-body {
            display: flex;
            justify-content: space-between;
            gap: 1.5rem;
            align-items: flex-start;
            flex-wrap: wrap;
        }

        .account-hero-content {
            max-width: 48rem;
        }

        .account-hero-eyebrow {
            margin: 0;
            font-size: 0.75rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #64748b;
        }

        .account-hero-warning .account-hero-eyebrow {
            color: #92400e;
        }

        .account-hero-title {
            margin: 0.45rem 0 0;
            font-size: clamp(1.35rem, 3vw, 1.8rem);
            line-height: 1.2;
            color: #0f172a;
            font-weight: var(--font-weight-bold);
        }

        .account-hero-text {
            margin: 0.7rem 0 0;
            color: #334155;
            max-width: 44rem;
        }

        .account-hero-actions {
            display: flex;
            gap: 0.6rem;
            flex-wrap: wrap;
        }

        .account-split-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .account-list {
            display: grid;
            gap: 0.75rem;
        }

        .account-item {
            padding: 1rem;
            border-radius: var(--radius-xl);
            border: 1px solid var(--color-border-default);
        }

        .account-item-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .account-item-main {
            display: flex;
            gap: 0.75rem;
            align-items: flex-start;
        }

        .account-item-icon {
            width: 2rem;
            height: 2rem;
            border-radius: var(--radius-full);
            display: grid;
            place-items: center;
            background: #eff6ff;
            color: #2563eb;
            flex-shrink: 0;
        }

        .account-item-title {
            margin: 0;
            font-weight: var(--font-weight-semibold);
            color: #0f172a;
        }

        .account-item-subtitle {
            margin: 0.2rem 0 0;
            color: #334155;
            font-size: 0.92rem;
        }

        .account-item-meta {
            margin: 0.45rem 0 0;
            color: #64748b;
            font-size: 0.78rem;
        }

        .account-item-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .status-pill {
            padding: 0.3rem 0.65rem;
            border-radius: var(--radius-full);
            font-size: 0.75rem;
            font-weight: var(--font-weight-semibold);
            display: inline-flex;
            align-items: center;
            white-space: nowrap;
        }

        .status-pill-success {
            background: #dcfce7;
            color: #166534;
        }

        .status-pill-info {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status-pill-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .status-pill-yellow {
            background: #fef9c3;
            color: #854d0e;
        }

        .status-pill-danger {
            background: #fee2e2;
            color: #b91c1c;
        }

        .status-pill-neutral {
            background: #e2e8f0;
            color: #334155;
        }

        .timeline-list {
            display: grid;
            gap: 1rem;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .timeline-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .timeline-dot {
            margin-top: 0.35rem;
            width: 0.55rem;
            height: 0.55rem;
            border-radius: var(--radius-full);
            background: #2563eb;
            flex-shrink: 0;
        }

        .timeline-content {
            padding-bottom: 0.9rem;
            border-bottom: 1px solid #e2e8f0;
            width: 100%;
        }

        .timeline-time {
            margin: 0;
            color: #64748b;
            font-size: 0.78rem;
        }

        .timeline-title {
            margin: 0.2rem 0 0;
            color: #0f172a;
            font-weight: var(--font-weight-semibold);
        }

        .timeline-body {
            margin: 0.3rem 0 0;
            color: #334155;
            font-size: 0.9rem;
        }

        .timeline-tag {
            display: inline-block;
            margin-top: 0.45rem;
            border-radius: var(--radius-full);
            background: #f1f5f9;
            color: #334155;
            font-size: 0.72rem;
            padding: 0.2rem 0.55rem;
        }

        .account-note-list {
            margin: 0;
            padding-left: 1rem;
            color: #334155;
            display: grid;
            gap: 0.45rem;
        }

        .account-meta-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .account-meta-card {
            border: 1px solid #e2e8f0;
            border-radius: var(--radius-xl);
            padding: 0.8rem;
        }

        .account-meta-label {
            margin: 0;
            color: #64748b;
            font-size: 0.78rem;
        }

        .account-meta-value {
            margin: 0.2rem 0 0;
            color: #0f172a;
            font-weight: var(--font-weight-bold);
        }

        .client-context-form-group {
            margin-bottom: 0;
        }

        @keyframes account-fade-up {
            from {
                opacity: 0;
                transform: translateY(6px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert {
            margin: 0;
        }

        [x-cloak] {
            display: none !important;
        }

        .account-confirm-dialog {
            border: 0;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            width: min(32rem, calc(100vw - 2rem));
            padding: 0;
        }

        .account-confirm-dialog::backdrop {
            background: rgba(15, 23, 42, 0.45);
        }

        .account-confirm-dialog-content {
            padding: 1rem;
            display: grid;
            gap: 0.75rem;
        }

        .account-confirm-dialog-title {
            margin: 0;
            font-size: 1rem;
            font-weight: var(--font-weight-semibold);
            color: var(--color-text-primary);
        }

        .account-confirm-dialog-text {
            margin: 0;
            color: var(--color-text-secondary);
            font-size: var(--font-size-sm);
        }

        .account-confirm-dialog-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
        }

        @media (max-width: 1023px) {
            .account-overlay {
                display: block;
                position: fixed;
                inset: 0;
                z-index: 35;
                background: rgba(15, 23, 42, 0.42);
            }

            .account-sidebar {
                transform: translateX(-100%);
                transition: transform var(--duration-medium) var(--ease-default);
                z-index: 40;
            }

            .account-sidebar.is-open {
                transform: translateX(0);
            }

            .account-sidebar-close {
                display: inline-flex;
            }

            .account-topbar {
                flex-direction: column;
                align-items: flex-start;
            }

            .account-user-actions {
                width: 100%;
                justify-content: space-between;
            }

            .account-main-inner {
                max-width: 100%;
            }

            .account-hero-content {
                max-width: 100%;
            }

            .account-meta-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    @stack('styles')
</head>
<body x-data="accountShell()">
@php
    $user = auth()->user();
    $availableClients = $user
        ? app(\App\Services\SocialCare\CareAccountReadService::class)->getClientsForUser($user)
        : collect();
    $activeClient = $user
        ? app(\App\Services\Account\AccountContextManager::class)->getActiveClient($user)
        : null;

    $navGroups = [
        [
            'label' => 'Основное',
            'items' => [
                ['label' => 'Обзор', 'route' => 'account.dashboard', 'active' => ['account.dashboard']],
                ['label' => 'Заказы', 'route' => 'account.orders.index', 'active' => ['account.orders.*']],
                ['label' => 'Новый заказ', 'route' => 'account.new-order.index', 'active' => ['account.new-order.*']],
                ['label' => 'Доставки', 'route' => 'account.deliveries.index', 'active' => ['account.deliveries.index', 'account.deliveries.show']],
                ['label' => 'Создать доставку', 'route' => 'account.deliveries.create', 'active' => ['account.deliveries.create']],
                ['label' => 'Платежи', 'route' => 'account.billing.index', 'active' => ['account.billing.*']],
            ],
        ],
        [
            'label' => 'Коммуникации',
            'items' => [
                ['label' => 'Уведомления', 'route' => 'account.notifications.index', 'active' => ['account.notifications.index']],
                ['label' => 'Лента уведомлений', 'route' => 'account.notifications.feed', 'active' => ['account.notifications.feed']],
                ['label' => 'Настройки уведомлений', 'route' => 'account.notifications.edit', 'active' => ['account.notifications.edit']],
            ],
        ],
        [
            'label' => 'Профиль',
            'items' => [
                ['label' => 'Настройки профиля', 'route' => 'account.profile.edit', 'active' => ['account.profile.*']],
                ['label' => 'Безопасность', 'route' => 'account.security.index', 'active' => ['account.security.*']],
            ],
        ],
    ];

    if (($hasSocialCareAccess ?? false) === true) {
        $navGroups[0]['items'][] = [
            'label' => 'Социальная помощь',
            'route' => 'account.care.dashboard',
            'active' => ['account.care.*'],
        ];
    }

    if (\Illuminate\Support\Facades\Route::has('account.classifieds.my-ads')) {
        $navGroups[] = [
            'label' => 'Маркетплейс',
            'items' => [
                ['label' => 'Мои объявления', 'route' => 'account.classifieds.my-ads', 'active' => ['account.classifieds.my-ads', 'account.classifieds.create', 'account.classifieds.edit']],
                ['label' => 'Избранное', 'route' => 'account.classifieds.favorites', 'active' => ['account.classifieds.favorites']],
                ['label' => 'Мой магазин', 'route' => 'account.classifieds.shop', 'active' => ['account.classifieds.shop']],
            ],
        ];
    }
@endphp

<a href="#account-main-content" class="skip-link">Перейти до основного контенту</a>

<div class="account-shell">
    <header class="mobile-header" aria-label="Мобильная навигация">
        <button
            type="button"
            class="mobile-menu-button"
            x-ref="menuToggle"
            @click="openNav()"
            aria-label="Открыть навигацию кабинета"
            aria-controls="account-sidebar-nav"
            :aria-expanded="accountNavOpen.toString()"
        >
            <svg class="mobile-menu-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M3 6h18M3 12h18M3 18h18" stroke-linecap="round"/>
            </svg>
        </button>
        <strong>GLF Bikube</strong>
        <span class="text-sm text-slate-500">Кабинет</span>
    </header>

    <div
        class="account-overlay"
        x-show="accountNavOpen"
        x-cloak
        x-transition.opacity
        @click="closeNav()"
        :aria-hidden="(!accountNavOpen).toString()"
    ></div>

    <aside
        id="account-sidebar-nav"
        class="sidebar account-sidebar"
        x-ref="sidebar"
        :class="accountNavOpen ? 'is-open' : ''"
        :aria-hidden="(!accountNavOpen).toString()"
        @keydown.escape.window="closeNav()"
        @keydown.tab="trapFocus($event)"
        aria-label="Навигация кабинета"
    >
        <div class="sidebar-header sidebar-brand">
            <a class="sidebar-brand-link" href="{{ route('home') }}">GLF Bikube</a>
            <button
                type="button"
                class="account-sidebar-close"
                @click="closeNav()"
                aria-label="Закрыть навигацию кабинета"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" aria-hidden="true">
                    <path d="M6 6l12 12M18 6L6 18" stroke-linecap="round"/>
                </svg>
            </button>
        </div>

        <nav class="sidebar-nav" aria-label="Основная навигация кабинета">
            @foreach($navGroups as $group)
                <section class="sidebar-section" aria-label="{{ $group['label'] }}">
                    <h2 class="sidebar-section-title">{{ $group['label'] }}</h2>
                    <ul class="sidebar-section-items">
                        @foreach($group['items'] as $item)
                            @php
                                $isActive = false;
                                foreach ($item['active'] as $pattern) {
                                    if (request()->routeIs($pattern)) {
                                        $isActive = true;
                                        break;
                                    }
                                }
                            @endphp
                            <li>
                                <a
                                    href="{{ route($item['route']) }}"
                                    class="sidebar-link"
                                    @click="if (window.matchMedia('(max-width: 1023px)').matches) closeNav()"
                                    @if($isActive) aria-current="page" @endif
                                >
                                    <span>{{ $item['label'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </nav>
    </aside>

    <main id="account-main-content" tabindex="-1" class="main-content account-main">
        <div class="account-main-inner">
            <header class="account-topbar" aria-label="Шапка кабинета">
                <div>
                    <h1 class="account-heading">@yield('header', 'Личный кабинет')</h1>
                    <p class="account-subheading">
                        Контекст:
                        @if($activeClient)
                            <strong>{{ $activeClient->full_name }}</strong>
                        @else
                            <strong>Личный профиль</strong>
                        @endif
                    </p>
                </div>

                <div class="account-user-actions">
                    <span class="account-user-chip" title="Текущий пользователь">{{ $user?->name ?? 'Пользователь' }}</span>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="btn btn-secondary btn-sm">Выйти</button>
                    </form>
                </div>
            </header>

            @include('account.partials.client-switcher', compact('availableClients', 'activeClient'))

            @if(session('status'))
                <div class="alert alert-success" role="status" aria-live="polite">
                    <div class="alert-content">
                        <p class="alert-text">{{ session('status') }}</p>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-error" role="alert" aria-live="assertive">
                    <div class="alert-content">
                        <p class="alert-text">{{ session('error') }}</p>
                    </div>
                </div>
            @endif

            <div class="account-content">
                @yield('content')
            </div>
            <div id="account-announcer" class="sr-only" role="status" aria-live="polite"></div>
        </div>
    </main>
</div>

<dialog id="account-confirm-dialog" class="account-confirm-dialog" aria-labelledby="account-confirm-title">
    <div class="account-confirm-dialog-content">
        <h2 id="account-confirm-title" class="account-confirm-dialog-title">Confirm action</h2>
        <p id="account-confirm-text" class="account-confirm-dialog-text">Are you sure you want to continue?</p>
        <div class="account-confirm-dialog-actions">
            <button type="button" class="btn btn-secondary btn-sm" data-confirm-cancel>Cancel</button>
            <button type="button" class="btn btn-primary btn-sm" data-confirm-accept>Continue</button>
        </div>
    </div>
</dialog>

<script>
    function accountShell() {
        return {
            accountNavOpen: false,
            lastFocusedElement: null,
            openNav() {
                this.lastFocusedElement = document.activeElement;
                this.accountNavOpen = true;
                document.body.style.overflow = 'hidden';

                this.$nextTick(() => {
                    const firstFocusable = this.getFocusableElements()[0];
                    if (firstFocusable) {
                        firstFocusable.focus();
                    }
                });
            },
            closeNav() {
                this.accountNavOpen = false;
                document.body.style.overflow = '';

                this.$nextTick(() => {
                    if (this.lastFocusedElement && typeof this.lastFocusedElement.focus === 'function') {
                        this.lastFocusedElement.focus();
                        return;
                    }

                    if (this.$refs.menuToggle) {
                        this.$refs.menuToggle.focus();
                    }
                });
            },
            getFocusableElements() {
                if (!this.$refs.sidebar) {
                    return [];
                }

                return Array.from(
                    this.$refs.sidebar.querySelectorAll(
                        'a[href], button:not([disabled]), textarea, input, select, [tabindex]:not([tabindex="-1"])'
                    )
                ).filter((element) => !element.hasAttribute('hidden'));
            },
            trapFocus(event) {
                if (!this.accountNavOpen || !window.matchMedia('(max-width: 1023px)').matches) {
                    return;
                }

                const focusable = this.getFocusableElements();
                if (focusable.length === 0) {
                    return;
                }

                const first = focusable[0];
                const last = focusable[focusable.length - 1];

                if (event.shiftKey && document.activeElement === first) {
                    event.preventDefault();
                    last.focus();
                } else if (!event.shiftKey && document.activeElement === last) {
                    event.preventDefault();
                    first.focus();
                }
            }
        };
    }

    document.addEventListener('DOMContentLoaded', () => {
        const dialog = document.getElementById('account-confirm-dialog');
        if (!dialog) {
            return;
        }

        const textEl = dialog.querySelector('#account-confirm-text');
        const acceptBtn = dialog.querySelector('[data-confirm-accept]');
        const cancelBtn = dialog.querySelector('[data-confirm-cancel]');
        let pendingForm = null;

        document.addEventListener('submit', (event) => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement)) {
                return;
            }

            if (form.hasAttribute('data-client-switch')) {
                const current = form.dataset.current || '';
                const selectedField = form.querySelector('[name=\"client_profile_id\"]');
                const selected = selectedField ? (selectedField.value || '') : '';
                if (current === selected) {
                    event.preventDefault();
                    return;
                }
            }

            const confirmMessage = form.getAttribute('data-confirm');
            if (!confirmMessage) {
                return;
            }

            if (form.dataset.confirmed === 'true') {
                form.dataset.confirmed = 'false';
                return;
            }

            event.preventDefault();

            if (typeof dialog.showModal !== 'function') {
                if (window.confirm(confirmMessage)) {
                    form.dataset.confirmed = 'true';
                    form.submit();
                }
                return;
            }

            pendingForm = form;
            textEl.textContent = confirmMessage;
            dialog.showModal();
            acceptBtn.focus();
        });

        const closeDialog = () => {
            if (dialog.open) {
                dialog.close();
            }
        };

        acceptBtn?.addEventListener('click', () => {
            if (!pendingForm) {
                closeDialog();
                return;
            }

            pendingForm.dataset.confirmed = 'true';
            const targetForm = pendingForm;
            pendingForm = null;
            closeDialog();
            targetForm.requestSubmit();
        });

        cancelBtn?.addEventListener('click', () => {
            pendingForm = null;
            closeDialog();
        });
    });
</script>

<script defer src="{{ asset('design-system/design-system.js') }}"></script>
@stack('scripts')
</body>
</html>
