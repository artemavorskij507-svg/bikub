@extends('account.layout')

@section('title', 'Мои претензии — Личный кабинет')
@section('header', 'Мои претензии')

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
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Реестр обращений</h2>
                <p class="mt-1 text-sm text-slate-600">Отслеживайте статусы и сроки обработки по SLA.</p>
            </div>
            <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                Всего: {{ $claims->total() }}
            </span>
        </div>
    </section>

    @if($claims->count() > 0)
        <section class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full" aria-label="Список претензий">
                    <caption class="sr-only">Список пользовательских претензий и статусов SLA</caption>
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">ID</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Тип</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Статус</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Приоритет</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">SLA</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Создано</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Действия</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($claims as $claim)
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-4 py-4 text-sm font-semibold text-slate-900">#{{ $claim->id }}</td>
                                <td class="px-4 py-4 text-sm text-slate-700">{{ $claim->type }}</td>
                                <td class="px-4 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses[$claim->status] ?? 'bg-slate-100 text-slate-700' }}">
                                        {{ ucfirst(str_replace('_', ' ', $claim->status)) }}
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $severityClasses[$claim->severity] ?? 'bg-slate-100 text-slate-700' }}">
                                        {{ ucfirst($claim->severity) }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-sm">
                                    @if($claim->sla_response_breached || $claim->sla_resolution_breached)
                                        <span class="font-semibold text-rose-600">Нарушен</span>
                                    @else
                                        <span class="font-medium text-emerald-700">В норме</span>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-sm text-slate-600">{{ $claim->created_at->format('d.m.Y H:i') }}</td>
                                <td class="px-4 py-4 text-sm">
                                    <a href="{{ route('account.claims.show', $claim) }}" class="font-medium text-primary-600 hover:text-primary-700">
                                        Открыть
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 px-4 py-4 sm:px-6">
                {{ $claims->links() }}
            </div>
        </section>
    @else
        <section class="bg-white border border-slate-200 rounded-xl p-10 text-center">
            <h3 class="text-base font-semibold text-slate-900">Пока нет претензий</h3>
            <p class="mt-2 text-sm text-slate-600">Когда вы создадите первое обращение, оно появится в этом разделе.</p>
        </section>
    @endif
</div>
@endsection
