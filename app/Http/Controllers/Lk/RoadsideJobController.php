<?php

namespace App\Http\Controllers\Lk;

use App\Http\Controllers\Controller;
use App\Models\RoadsideEmergency;
use Illuminate\Http\Request;

class RoadsideJobController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $filter = $request->get('filter', 'active');

        // Получаем RoadsideEmergency, где:
        // 1. order->assigned_to = user->id (назначен через Order)
        // 2. road_helper_id->user_id = user->id (назначен через RoadHelperProfile)
        $baseQuery = RoadsideEmergency::query()
            ->where(function ($q) use ($user) {
                $q->whereHas('order', function ($oq) use ($user) {
                    $oq->where('assigned_to', $user->id);
                })
                    ->orWhereHas('helper', function ($hq) use ($user) {
                        $hq->where('user_id', $user->id);
                    });
            })
            ->with(['order', 'order.user', 'partner', 'helper.user', 'customer'])
            ->orderByDesc('created_at');

        $activeJobsQuery = (clone $baseQuery)->active();
        $completedJobsQuery = (clone $baseQuery)->whereIn('status', [
            RoadsideEmergency::STATUS_COMPLETED,
            RoadsideEmergency::STATUS_CANCELLED,
            RoadsideEmergency::STATUS_REJECTED,
            RoadsideEmergency::STATUS_FAILED,
        ]);

        $activeJobs = $activeJobsQuery->get();
        $completedJobs = $completedJobsQuery->limit(30)->get();

        return view('lk.roadside.index', [
            'filter' => $filter,
            'activeJobs' => $activeJobs,
            'completedJobs' => $completedJobs,
        ]);
    }

    public function show(Request $request, RoadsideEmergency $job)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Защита: чужие задания не смотреть
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

        $job->load(['order', 'order.user', 'order.assignedUser', 'partner', 'helper.user', 'customer']);

        // Собираем таймлайн на основе timestamps из metadata
        $metadata = $job->metadata ?? [];
        $timeline = [
            'created_at' => $job->created_at,
            'assigned_at' => $metadata['assigned_at'] ?? null,
            'en_route_at' => $metadata['en_route_at'] ?? null,
            'on_spot_at' => $metadata['on_site_at'] ?? null,
            'started_at' => $metadata['started_at'] ?? null,
            'completed_at' => $metadata['completed_at'] ?? ($job->order?->completed_at ?? null),
            'cancelled_at' => $metadata['cancelled_at'] ?? null,
        ];

        return view('lk.roadside.show', [
            'job' => $job,
            'timeline' => $timeline,
        ]);
    }
}
