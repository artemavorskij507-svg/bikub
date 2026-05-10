@extends('executor.layout')

@section('title', 'Мои задачи — Кабинет мастера')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-slate-900">Мои задачи</h1>
        <div class="text-sm text-slate-600">
            <span class="font-medium">{{ $profile->user->name }}</span>
            @if($profile->rating)
                <span class="ml-2">⭐ {{ number_format($profile->rating, 1) }}</span>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Тип заказа</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Адрес</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Описание</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Статус</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Действия</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-200">
                @forelse($assignments as $assignment)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                            #{{ $assignment->id }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                            @php
                                $serviceType = \App\Enums\ServiceType::tryFrom($assignment->order->service_type);
                            @endphp
                            {{ $serviceType ? $serviceType->label() : $assignment->order->service_type }}
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600">
                            @if($assignment->order->handymanDetails)
                                {{ $assignment->order->handymanDetails->address_line }}, {{ $assignment->order->handymanDetails->city }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600">
                            @if($assignment->order->handymanDetails)
                                {{ Str::limit($assignment->order->handymanDetails->description, 50) }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
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
                            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $statusColors[$assignment->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ $statusLabels[$assignment->status] ?? $assignment->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="{{ route('executor.jobs.show', $assignment) }}" class="text-indigo-600 hover:text-indigo-900">
                                Открыть
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-sm text-slate-500">
                            Нет назначенных задач
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($assignments->hasPages())
        <div class="mt-4">
            {{ $assignments->links() }}
        </div>
    @endif
</div>
@endsection


