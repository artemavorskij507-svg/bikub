<?php

namespace App\Http\Controllers\Lk;

use App\Events\HandymanAssignmentStatusChanged;
use App\Events\HandymanJobCompleted;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Executor\UpdateJobStatusRequest;
use App\Models\HandymanAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExecutorJobsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $profile = $user->executorProfile;

        // Автоподключение профиля исполнителя для пользователей с соответствующей ролью
        if (! $profile) {
            $canAutoProvision = $user->hasAnyRole([
                'executor',
                'courier',
                'roadside_assist',
                'eco_executor',
                'admin',
                'operator',
            ]);

            if ($canAutoProvision) {
                $profile = \App\Models\Moving\ExecutorProfile::create([
                    'user_id' => $user->id,
                    'vehicle_type' => 'van',
                    'skills' => ['delivery', 'handyman', 'roadside'],
                    'max_volume' => 12,
                    'max_weight' => 800,
                    'insurance_limit' => 100000,
                    'rating' => 5.0,
                    'completed_orders_count' => 0,
                    'is_active' => true,
                    'last_active_at' => now(),
                    'metadata' => ['auto_provisioned' => true],
                ]);
            } else {
                abort(403, 'У вас нет профиля исполнителя');
            }
        }

        $assignments = HandymanAssignment::query()
            ->with(['order', 'order.handymanDetails', 'repairProject'])
            ->where('executor_profile_id', $profile->id)
            ->orderByRaw("
                CASE status
                    WHEN 'proposed' THEN 1
                    WHEN 'accepted' THEN 2
                    WHEN 'in_progress' THEN 3
                    WHEN 'started' THEN 4
                    WHEN 'completed' THEN 5
                    ELSE 6
                END
            ")
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('lk.executor.jobs', [
            'profile' => $profile,
            'assignments' => $assignments,
        ]);
    }

    public function show(Request $request, HandymanAssignment $assignment)
    {
        $this->authorizeAssignment($request, $assignment);

        $assignment->load(['order', 'order.handymanDetails', 'order.repairProject']);

        return view('lk.executor.job-show', [
            'assignment' => $assignment,
        ]);
    }

    public function accept(Request $request, HandymanAssignment $assignment)
    {
        $this->authorizeAssignment($request, $assignment);

        if ($assignment->status !== 'proposed') {
            return back()->withErrors('Задачу нельзя принять в текущем статусе.');
        }

        $oldStatus = $assignment->status;

        $assignment->update([
            'status' => 'accepted',
            'is_primary' => true,
        ]);

        $assignment->refresh();

        $this->dispatchAssignmentEvents($assignment, $oldStatus);

        return back()->with('status', 'Задача принята.');
    }

    public function decline(Request $request, HandymanAssignment $assignment)
    {
        $this->authorizeAssignment($request, $assignment);

        if (! in_array($assignment->status, ['proposed', 'accepted'], true)) {
            return back()->withErrors('Задачу нельзя отклонить в текущем статусе.');
        }

        $oldStatus = $assignment->status;

        $assignment->update(['status' => 'declined']);

        $assignment->refresh();

        $this->dispatchAssignmentEvents($assignment, $oldStatus);

        return redirect()->route('lk.executor.jobs.index')->with('status', 'Задача отклонена.');
    }

    public function updateStatus(UpdateJobStatusRequest $request, HandymanAssignment $assignment)
    {
        $this->authorizeAssignment($request, $assignment);

        $status = $request->input('status'); // in_route, started, finished

        $oldStatus = $assignment->status;

        DB::transaction(function () use ($assignment, $status, $oldStatus) {
            $data = [
                'status' => $status === 'finished' ? 'completed' : $assignment->status,
            ];

            $now = now();

            if ($status === 'in_route' && ! $assignment->planned_start_at) {
                $data['planned_start_at'] = $now;
            }

            if ($status === 'started' && ! $assignment->actual_start_at) {
                $data['actual_start_at'] = $now;
            }

            if ($status === 'finished' && ! $assignment->actual_finish_at) {
                $data['actual_finish_at'] = $now;
            }

            $assignment->update($data);

            if ($status === 'finished') {
                $executor = $assignment->executorProfile;
                $executor->increment('completed_orders_count');
            }

            $assignment->refresh();

            $this->dispatchAssignmentEvents($assignment, $oldStatus);
        });

        return back()->with('status', 'Статус задачи обновлён.');
    }

    protected function authorizeAssignment(Request $request, HandymanAssignment $assignment): void
    {
        $user = $request->user();
        $profile = $user->executorProfile;

        // Автоподключение профиля исполнителя для пользователей с соответствующей ролью
        if (! $profile) {
            $canAutoProvision = $user->hasAnyRole([
                'executor',
                'courier',
                'roadside_assist',
                'eco_executor',
                'admin',
                'operator',
            ]);

            if ($canAutoProvision) {
                $profile = \App\Models\Moving\ExecutorProfile::create([
                    'user_id' => $user->id,
                    'vehicle_type' => 'van',
                    'skills' => ['delivery', 'handyman', 'roadside'],
                    'max_volume' => 12,
                    'max_weight' => 800,
                    'insurance_limit' => 100000,
                    'rating' => 5.0,
                    'completed_orders_count' => 0,
                    'is_active' => true,
                    'last_active_at' => now(),
                    'metadata' => ['auto_provisioned' => true],
                ]);
            }
        }

        if (! $profile || $assignment->executor_profile_id !== $profile->id) {
            abort(403);
        }
    }

    protected function dispatchAssignmentEvents(HandymanAssignment $assignment, string $oldStatus): void
    {
        if ($oldStatus !== $assignment->status) {
            event(new HandymanAssignmentStatusChanged($assignment, $oldStatus, $assignment->status));

            if ($assignment->status === 'completed') {
                event(new HandymanJobCompleted($assignment));
            }
        }
    }
}
