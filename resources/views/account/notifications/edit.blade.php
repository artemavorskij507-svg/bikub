@extends('account.layout')

@section('title', 'Уведомления — Личный кабинет')
@section('header', 'Настройки уведомлений')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    <section class="bg-white border border-slate-200 rounded-xl p-5 sm:p-6">
        <h2 class="text-lg font-semibold text-slate-900">Параметры уведомлений Social Care</h2>
        <p class="mt-1 text-sm text-slate-600">
            Выберите события, о которых хотите получать уведомления.
        </p>
    </section>

    <section class="bg-white border border-slate-200 rounded-xl p-5 sm:p-6">
        <form method="POST" action="{{ route('account.notifications.update') }}" class="space-y-4" aria-label="Настройки уведомлений">
            @csrf
            @method('PUT')

            <label class="flex gap-3 rounded-lg border border-slate-200 p-4 hover:bg-slate-50">
                <input type="checkbox" name="notify_care_order_created" value="1" {{ old('notify_care_order_created', $settings->notify_care_order_created) ? 'checked' : '' }} class="mt-1 rounded border-slate-300">
                <span>
                    <span class="block font-medium text-slate-900">Создание нового заказа</span>
                    <span class="mt-1 block text-sm text-slate-600">Сообщать, когда создается новый заказ социальной помощи.</span>
                </span>
            </label>

            <label class="flex gap-3 rounded-lg border border-slate-200 p-4 hover:bg-slate-50">
                <input type="checkbox" name="notify_care_plan_created" value="1" {{ old('notify_care_plan_created', $settings->notify_care_plan_created) ? 'checked' : '' }} class="mt-1 rounded border-slate-300">
                <span>
                    <span class="block font-medium text-slate-900">Создание плана заботы</span>
                    <span class="mt-1 block text-sm text-slate-600">Сообщать, когда формируется новый план заботы.</span>
                </span>
            </label>

            <label class="flex gap-3 rounded-lg border border-slate-200 p-4 hover:bg-slate-50">
                <input type="checkbox" name="notify_visit_status_changes" value="1" {{ old('notify_visit_status_changes', $settings->notify_visit_status_changes) ? 'checked' : '' }} class="mt-1 rounded border-slate-300">
                <span>
                    <span class="block font-medium text-slate-900">Изменения статуса визита</span>
                    <span class="mt-1 block text-sm text-slate-600">Назначение, перенос или отмена визита.</span>
                </span>
            </label>

            <label class="flex gap-3 rounded-lg border border-slate-200 p-4 hover:bg-slate-50">
                <input type="checkbox" name="notify_visit_reports" value="1" {{ old('notify_visit_reports', $settings->notify_visit_reports) ? 'checked' : '' }} class="mt-1 rounded border-slate-300">
                <span>
                    <span class="block font-medium text-slate-900">Отчеты о визитах</span>
                    <span class="mt-1 block text-sm text-slate-600">Сообщать о новых отчетах от помощника.</span>
                </span>
            </label>

            <label class="flex gap-3 rounded-lg border border-slate-200 p-4 hover:bg-slate-50">
                <input type="checkbox" name="notify_emergency" value="1" {{ old('notify_emergency', $settings->notify_emergency) ? 'checked' : '' }} class="mt-1 rounded border-slate-300">
                <span>
                    <span class="block font-medium text-slate-900">Экстренные ситуации</span>
                    <span class="mt-1 block text-sm text-slate-600">Критические события и сигналы срочной помощи.</span>
                </span>
            </label>

            <label class="flex gap-3 rounded-lg border border-slate-200 p-4 hover:bg-slate-50">
                <input type="checkbox" name="notify_reschedule_requests" value="1" {{ old('notify_reschedule_requests', $settings->notify_reschedule_requests) ? 'checked' : '' }} class="mt-1 rounded border-slate-300">
                <span>
                    <span class="block font-medium text-slate-900">Запросы на перенос</span>
                    <span class="mt-1 block text-sm text-slate-600">Уведомления о запросах на перенос визита.</span>
                </span>
            </label>

            <div class="flex justify-end border-t border-slate-200 pt-4">
                <button type="submit" class="inline-flex items-center rounded-lg bg-primary-600 px-6 py-2.5 text-sm font-medium text-white hover:bg-primary-700">
                    Сохранить настройки
                </button>
            </div>
        </form>
    </section>
</div>
@endsection
