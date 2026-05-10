<?php

namespace App\Http\Controllers\Lk;

use App\Http\Controllers\Controller;
use App\Models\RoadsideEmergency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RoadsideJobActionController extends Controller
{
    public function handle(Request $request, RoadsideEmergency $job)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Защита: проверяем, что задание назначено текущему пользователю
        $isAssigned = false;
        if ($job->order && $job->order->assigned_to === $user->id) {
            $isAssigned = true;
        }
        if ($job->helper && $job->helper->user_id === $user->id) {
            $isAssigned = true;
        }

        if (! $isAssigned && ! $user->hasAnyRole(['admin', 'operator', 'dispatcher'])) {
            abort(403, 'У вас нет доступа к этому заданию');
        }

        $data = $request->validate([
            'action' => ['required', Rule::in([
                'accept',
                'reject',
                'start_travel',
                'arrived',
                'start_job',
                'finish_job',
                'cancel',
            ])],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($job, $user, $data) {
            $action = $data['action'];
            $metadata = $job->metadata ?? [];

            switch ($action) {
                case 'accept':
                    // Только если new и не назначен
                    if (! $job->isNew() || ($job->helper && $job->helper->user_id !== $user->id && $job->order && $job->order->assigned_to !== $user->id)) {
                        abort(422, 'Это задание уже обработано.');
                    }

                    // Назначаем через helper, если у пользователя есть RoadHelperProfile
                    $helperProfile = $user->roadHelperProfile ?? null;
                    if ($helperProfile) {
                        $job->road_helper_id = $helperProfile->id;
                    }

                    // Также обновляем Order, если есть
                    if ($job->order) {
                        $job->order->assigned_to = $user->id;
                        $job->order->save();
                    }

                    $job->status = RoadsideEmergency::STATUS_ASSIGNED;
                    $metadata['assigned_at'] = now()->toIso8601String();
                    break;

                case 'reject':
                    // Только если new/assigned
                    if (! in_array($job->status, [RoadsideEmergency::STATUS_NEW, RoadsideEmergency::STATUS_ASSIGNED], true)) {
                        abort(422, 'Нельзя отклонить задание в текущем статусе.');
                    }

                    $job->status = RoadsideEmergency::STATUS_REJECTED;
                    $metadata['cancelled_at'] = now()->toIso8601String();
                    $metadata['cancel_reason'] = $data['reason'] ?? 'Исполнитель отклонил задание';
                    break;

                case 'start_travel':
                    if (! in_array($job->status, [RoadsideEmergency::STATUS_ASSIGNED, RoadsideEmergency::STATUS_NEW], true)) {
                        abort(422, 'Нельзя выехать в текущем статусе.');
                    }

                    $job->status = RoadsideEmergency::STATUS_ON_ROUTE;
                    $metadata['en_route_at'] = now()->toIso8601String();
                    break;

                case 'arrived':
                    if ($job->status !== RoadsideEmergency::STATUS_ON_ROUTE) {
                        abort(422, 'Нельзя отметить прибытие в текущем статусе.');
                    }

                    $job->status = RoadsideEmergency::STATUS_ON_SPOT;
                    $metadata['on_site_at'] = now()->toIso8601String();
                    break;

                case 'start_job':
                    if (! in_array($job->status, [RoadsideEmergency::STATUS_ON_SPOT, RoadsideEmergency::STATUS_ON_ROUTE], true)) {
                        abort(422, 'Нельзя начать работу в текущем статусе.');
                    }

                    $job->status = RoadsideEmergency::STATUS_IN_PROGRESS;
                    $metadata['started_at'] = now()->toIso8601String();
                    break;

                case 'finish_job':
                    if ($job->status !== RoadsideEmergency::STATUS_IN_PROGRESS) {
                        abort(422, 'Нельзя завершить в текущем статусе.');
                    }

                    $job->status = RoadsideEmergency::STATUS_COMPLETED;
                    $metadata['completed_at'] = now()->toIso8601String();
                    break;

                case 'cancel':
                    if ($job->isDone()) {
                        abort(422, 'Нельзя отменить завершённое задание.');
                    }

                    $job->status = RoadsideEmergency::STATUS_CANCELLED;
                    $metadata['cancelled_at'] = now()->toIso8601String();
                    $metadata['cancel_reason'] = $data['reason'] ?? 'Отменено исполнителем';
                    break;
            }

            // Дописываем metadata-историю
            $metadata['worker_actions'] = $metadata['worker_actions'] ?? [];
            $metadata['worker_actions'][] = [
                'user_id' => $user->id,
                'action' => $data['action'],
                'reason' => $data['reason'] ?? null,
                'at' => now()->toIso8601String(),
            ];

            $job->metadata = $metadata;
            $job->save();

            // Синхронизируем статус Order
            $job->syncOrderStatus();

            // Очищаем кеш
            cache()->forget("user_{$user->id}_roadside_jobs_active_count");
            cache()->forget("user_{$user->id}_active_orders_count");
        });

        return redirect()
            ->route('lk.roadside-jobs.show', $job)
            ->with('status', 'Статус задания обновлён.');
    }
}
