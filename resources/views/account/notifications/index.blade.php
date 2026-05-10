@extends('account.layout')

@section('title', 'Уведомления')
@section('header', 'Уведомления')

@section('content')
@php
    $unreadCount = $notifications->whereNull('read_at')->count();
@endphp

<div class="mx-auto max-w-5xl space-y-6">
    <section class="bg-white border border-slate-200 rounded-xl p-5 sm:p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">Центр уведомлений</h2>
                <p class="mt-1 text-sm text-slate-600">
                    В этом разделе собраны ключевые события по заказам, ремонту и претензиям.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                    Всего: {{ $notifications->count() }}
                </span>
                <span class="inline-flex items-center rounded-full bg-primary-600 px-3 py-1 text-xs font-semibold text-white">
                    Новых: {{ $unreadCount }}
                </span>
                <a href="{{ route('account.notifications.feed') }}" class="text-xs font-medium text-primary-600 hover:text-primary-700 underline">
                    Расширенная лента
                </a>
            </div>
        </div>
    </section>

    <section class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <ul class="divide-y divide-slate-100">
            @forelse($notifications as $notification)
                @php
                    $data = $notification->data ?? [];
                    $isUnread = is_null($notification->read_at);
                    $type = $data['type'] ?? null;

                    $icon = '📬';
                    $title = 'Уведомление';
                    $badge = 'Система';

                    switch ($type) {
                        case 'handyman.order_created':
                            $icon = '🔧';
                            $title = 'Заказ мастера принят в работу';
                            $badge = 'Мастер';
                            break;
                        case 'handyman.assignment_status':
                            $icon = '🛠';
                            $title = 'Изменился статус задания';
                            $badge = 'Мастер';
                            break;
                        case 'repair.project_created':
                            $icon = '🏗';
                            $title = 'Создан проект ремонта';
                            $badge = 'Ремонт';
                            break;
                        case 'repair.update':
                            $icon = '📈';
                            $title = 'Новое обновление по ремонту';
                            $badge = 'Ремонт';
                            break;
                        case 'claim.status_changed':
                            $icon = '⚖️';
                            $title = 'Обновлен статус претензии';
                            $badge = 'Претензии';
                            break;
                    }
                @endphp

                <li class="{{ $isUnread ? 'bg-primary-50/50' : 'bg-white' }}">
                    <article class="px-4 py-4 sm:px-5">
                        <div class="flex items-start gap-3">
                            <div class="text-xl">{{ $icon }}</div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-sm font-semibold text-slate-900">{{ $title }}</h3>
                                        <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700">{{ $badge }}</span>
                                        @if($isUnread)
                                            <span class="inline-flex rounded-full bg-primary-600 px-2 py-0.5 text-[11px] font-semibold text-white">Новое</span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-slate-500">{{ $notification->created_at->format('d.m.Y H:i') }}</p>
                                </div>

                                @if(!empty($data['message'] ?? null))
                                    <p class="mt-2 text-sm text-slate-700">{{ $data['message'] }}</p>
                                @endif

                                <div class="mt-3 flex flex-wrap items-center gap-3 text-xs">
                                    @if(isset($data['order_id']))
                                        <a href="{{ route('account.orders.show', $data['order_id']) }}" class="font-medium text-primary-600 hover:text-primary-700">
                                            Открыть заказ #{{ $data['order_id'] }}
                                        </a>
                                    @endif
                                    @if(isset($data['project_id']))
                                        <a href="{{ route('account.repairs.show', $data['project_id']) }}" class="font-medium text-primary-600 hover:text-primary-700">
                                            Открыть проект ремонта
                                        </a>
                                    @endif

                                    @if($isUnread)
                                        <form method="POST" action="{{ route('account.notifications.read', $notification) }}">
                                            @csrf
                                            <button class="font-medium text-primary-600 hover:text-primary-700 underline">
                                                Отметить прочитанным
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </article>
                </li>
            @empty
                <li class="px-5 py-10 text-center text-sm text-slate-500">
                    У вас пока нет уведомлений.
                </li>
            @endforelse
        </ul>

        <footer class="border-t border-slate-100 px-5 py-3 text-[11px] text-slate-500">
            Показаны последние 50 уведомлений. Для расширенной фильтрации используйте раздел «Расширенная лента».
        </footer>
    </section>
</div>
@endsection
