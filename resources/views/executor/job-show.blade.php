@extends('executor.layout')

@section('title', 'Задача #' . $assignment->id . ' — Кабинет мастера')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('executor.dashboard') }}" class="text-blue-600 hover:underline mb-2 block">&larr; Назад к задачам</a>
            <h1 class="text-2xl font-bold text-slate-900">Задача #{{ $assignment->id }}</h1>
        </div>
        @php
            $statusLabels = [
                'proposed' => 'Предложено',
                'accepted' => 'Принято',
                'declined' => 'Отклонено',
                'reassigned' => 'Переназначено',
                'cancelled' => 'Отменено',
                'completed' => 'Завершено',
            ];
            $statusColors = [
                'proposed' => 'bg-yellow-100 text-yellow-800',
                'accepted' => 'bg-blue-100 text-blue-800',
                'declined' => 'bg-red-100 text-red-800',
                'reassigned' => 'bg-gray-100 text-gray-800',
                'cancelled' => 'bg-gray-100 text-gray-800',
                'completed' => 'bg-green-100 text-green-800',
            ];
        @endphp
        <span class="px-3 py-1 text-sm font-medium rounded-full {{ $statusColors[$assignment->status] ?? 'bg-gray-100 text-gray-800' }}">
            {{ $statusLabels[$assignment->status] ?? $assignment->status }}
        </span>
    </div>

    <!-- Информация о заказе -->
    <div class="bg-white rounded-lg border border-slate-200 p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Информация о заказе</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
                <span class="font-medium text-slate-700">Номер заказа:</span>
                <span class="ml-2 text-slate-900">#{{ $assignment->order->order_number ?? $assignment->order->id }}</span>
            </div>
            <div>
                <span class="font-medium text-slate-700">Тип услуги:</span>
                <span class="ml-2 text-slate-900">
                    @php
                        $serviceType = \App\Enums\ServiceType::tryFrom($assignment->order->service_type);
                    @endphp
                    {{ $serviceType ? $serviceType->label() : $assignment->order->service_type }}
                </span>
            </div>
            @if($assignment->order->estimated_total)
                <div>
                    <span class="font-medium text-slate-700">Оценочная стоимость:</span>
                    <span class="ml-2 text-slate-900">{{ number_format($assignment->order->estimated_total / 100, 2) }} NOK</span>
                </div>
            @endif
        </div>
    </div>

    @if($assignment->order->handymanDetails)
        <!-- Описание задачи -->
        <div class="bg-white rounded-lg border border-slate-200 p-6">
            <h2 class="text-lg font-semibold text-slate-900 mb-4">Описание задачи</h2>
            <div class="space-y-4 text-sm">
                <div>
                    <span class="font-medium text-slate-700">Описание:</span>
                    <div class="mt-1 text-slate-900 whitespace-pre-wrap">{{ $assignment->order->handymanDetails->description }}</div>
                </div>
                @if($assignment->order->handymanDetails->context_notes)
                    <div>
                        <span class="font-medium text-slate-700">Дополнительные заметки:</span>
                        <div class="mt-1 text-slate-900 whitespace-pre-wrap">{{ $assignment->order->handymanDetails->context_notes }}</div>
                    </div>
                @endif
                @if($assignment->order->handymanDetails->expected_duration_minutes)
                    <div>
                        <span class="font-medium text-slate-700">Ожидаемая длительность:</span>
                        <span class="ml-2 text-slate-900">{{ $assignment->order->handymanDetails->expected_duration_minutes }} минут</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Адрес -->
        <div class="bg-white rounded-lg border border-slate-200 p-6">
            <h2 class="text-lg font-semibold text-slate-900 mb-4">Адрес</h2>
            <div class="text-sm text-slate-900">
                <div>{{ $assignment->order->handymanDetails->address_line }}</div>
                <div>{{ $assignment->order->handymanDetails->postal_code }} {{ $assignment->order->handymanDetails->city }}</div>
            </div>
        </div>

        <!-- Желаемое время -->
        @if($assignment->order->handymanDetails->desired_start_at || $assignment->order->handymanDetails->desired_finish_at)
            <div class="bg-white rounded-lg border border-slate-200 p-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Желаемое время</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    @if($assignment->order->handymanDetails->desired_start_at)
                        <div>
                            <span class="font-medium text-slate-700">Начало:</span>
                            <span class="ml-2 text-slate-900">{{ $assignment->order->handymanDetails->desired_start_at->format('d.m.Y H:i') }}</span>
                        </div>
                    @endif
                    @if($assignment->order->handymanDetails->desired_finish_at)
                        <div>
                            <span class="font-medium text-slate-700">Окончание:</span>
                            <span class="ml-2 text-slate-900">{{ $assignment->order->handymanDetails->desired_finish_at->format('d.m.Y H:i') }}</span>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    @endif

    <!-- Действия -->
    <div class="bg-white rounded-lg border border-slate-200 p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Действия</h2>
        <div class="flex flex-wrap gap-3">
            @if($assignment->status === 'proposed')
                <form method="POST" action="{{ route('executor.jobs.accept', $assignment) }}" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        Принять задачу
                    </button>
                </form>
                <form method="POST" action="{{ route('executor.jobs.decline', $assignment) }}" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                        Отклонить задачу
                    </button>
                </form>
            @endif

            @if(in_array($assignment->status, ['accepted', 'in_progress']))
                @if($assignment->status === 'accepted')
                    <form method="POST" action="{{ route('executor.jobs.status', $assignment) }}" class="inline">
                        @csrf
                        <input type="hidden" name="status" value="in_route">
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            В пути
                        </button>
                    </form>
                @endif
                @if(!$assignment->actual_start_at)
                    <form method="POST" action="{{ route('executor.jobs.status', $assignment) }}" class="inline">
                        @csrf
                        <input type="hidden" name="status" value="started">
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                            Начал работу
                        </button>
                    </form>
                @endif
                <form method="POST" action="{{ route('executor.jobs.status', $assignment) }}" class="inline">
                    @csrf
                    <input type="hidden" name="status" value="finished">
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        Завершить работу
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Временные метки -->
    <div class="bg-white rounded-lg border border-slate-200 p-6">
        <h2 class="text-lg font-semibold text-slate-900 mb-4">Временные метки</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            @if($assignment->planned_start_at)
                <div>
                    <span class="font-medium text-slate-700">Планируемое начало:</span>
                    <span class="ml-2 text-slate-900">{{ $assignment->planned_start_at->format('d.m.Y H:i') }}</span>
                </div>
            @endif
            @if($assignment->planned_finish_at)
                <div>
                    <span class="font-medium text-slate-700">Планируемое окончание:</span>
                    <span class="ml-2 text-slate-900">{{ $assignment->planned_finish_at->format('d.m.Y H:i') }}</span>
                </div>
            @endif
            @if($assignment->actual_start_at)
                <div>
                    <span class="font-medium text-slate-700">Фактическое начало:</span>
                    <span class="ml-2 text-slate-900">{{ $assignment->actual_start_at->format('d.m.Y H:i') }}</span>
                </div>
            @endif
            @if($assignment->actual_finish_at)
                <div>
                    <span class="font-medium text-slate-700">Фактическое окончание:</span>
                    <span class="ml-2 text-slate-900">{{ $assignment->actual_finish_at->format('d.m.Y H:i') }}</span>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection


