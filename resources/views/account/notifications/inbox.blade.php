@extends('account.layout')

@section('title', 'Уведомления')
@section('header', 'Центр уведомлений')

@section('content')
<div class="space-y-6">
    <section class="bg-white border border-slate-200 rounded-xl p-4 sm:p-5">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <form method="GET" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-[1fr,auto,auto]" aria-label="Фильтр уведомлений">
                <div>
                    <label for="notification-category" class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Категория</label>
                    <select id="notification-category" name="category" class="mt-1 rounded-lg border-slate-300 text-sm">
                        <option value="">Все категории</option>
                        <option value="order" {{ $currentCategory === 'order' ? 'selected' : '' }}>Заказы</option>
                        <option value="social_care" {{ $currentCategory === 'social_care' ? 'selected' : '' }}>Social Care</option>
                        <option value="security" {{ $currentCategory === 'security' ? 'selected' : '' }}>Безопасность</option>
                    </select>
                </div>

                <label class="inline-flex min-h-[42px] items-center gap-2 rounded-lg border border-slate-200 px-3 text-sm text-slate-700">
                    <input type="checkbox" name="unread" value="1" {{ $onlyUnread ? 'checked' : '' }} class="rounded border-slate-300">
                    Только непрочитанные
                </label>

                <div class="flex gap-2">
                    <x-primary-button class="justify-center">Фильтровать</x-primary-button>
                    <a href="{{ route('account.notifications.feed') }}" class="inline-flex min-h-[42px] items-center justify-center rounded-lg border border-slate-300 px-4 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                        Сбросить
                    </a>
                </div>
            </form>

            <form method="POST" action="{{ route('account.notifications.feed.mark-all-read') }}">
                @csrf
                <x-secondary-button>Отметить все как прочитанные</x-secondary-button>
            </form>
        </div>
    </section>

    <section class="bg-white border border-slate-200 rounded-xl divide-y divide-slate-100">
        @forelse($notifications as $notification)
            <article class="p-4 sm:p-5 {{ is_null($notification->read_at) ? 'bg-primary-50/40' : '' }}">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <p class="text-xs uppercase tracking-wide text-slate-500">{{ $notification->category ?? 'Общее' }}</p>
                        <h3 class="mt-1 text-base font-semibold text-slate-900">{{ $notification->title }}</h3>
                        @if($notification->body)
                            <p class="mt-1 text-sm text-slate-600">{{ $notification->body }}</p>
                        @endif
                        <p class="mt-2 text-xs text-slate-400">Получено {{ $notification->created_at->diffForHumans() }}</p>
                    </div>

                    @if(is_null($notification->read_at))
                        <form method="POST" action="{{ route('account.notifications.feed.mark-read') }}">
                            @csrf
                            <input type="hidden" name="id" value="{{ $notification->id }}">
                            <x-secondary-button>Прочитано</x-secondary-button>
                        </form>
                    @else
                        <p class="text-xs text-slate-400">Прочитано {{ $notification->read_at->diffForHumans() }}</p>
                    @endif
                </div>
            </article>
        @empty
            <div class="p-8 text-center text-sm text-slate-500">Нет уведомлений по выбранным фильтрам.</div>
        @endforelse
    </section>

    <div>
        {{ $notifications->links() }}
    </div>
</div>
@endsection
