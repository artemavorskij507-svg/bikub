<?php

namespace App\Http\Controllers\Api\V1\Executor;

use App\Events\HandymanAssignmentStatusChanged;
use App\Events\HandymanJobCompleted;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Executor\AddMaterialsRequest;
use App\Http\Requests\Api\Executor\UpdateJobStatusRequest;
use App\Http\Resources\Executor\ExecutorAssignmentResource;
use App\Models\HandymanAssignment;
use App\Models\HandymanMaterialsEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExecutorJobsController extends Controller
{
    public function index(Request $request)
    {
        $profile = $request->user()->executorProfile;

        $status = $request->query('status'); // optional: proposed/accepted/in_progress/completed

        $query = HandymanAssignment::query()
            ->with(['order', 'order.handymanDetails'])
            ->where('executor_profile_id', $profile->id)
            ->orderByDesc('created_at');

        if ($status) {
            $query->where('status', $status);
        }

        $assignments = $query->paginate(20);

        return ExecutorAssignmentResource::collection($assignments);
    }

    public function show(Request $request, HandymanAssignment $assignment)
    {
        $this->authorizeAssignment($request, $assignment);

        $assignment->load(['order', 'order.handymanDetails']);

        return new ExecutorAssignmentResource($assignment);
    }

    public function accept(Request $request, HandymanAssignment $assignment)
    {
        $this->authorizeAssignment($request, $assignment);

        if ($assignment->status !== 'proposed') {
            abort(422, 'Задачу нельзя принять в текущем статусе.');
        }

        $oldStatus = $assignment->status;

        $assignment->update([
            'status' => 'accepted',
            'is_primary' => true,
        ]);

        $assignment->refresh();

        $this->dispatchAssignmentEvents($assignment, $oldStatus);

        return response()->json(['status' => 'ok']);
    }

    public function decline(Request $request, HandymanAssignment $assignment)
    {
        $this->authorizeAssignment($request, $assignment);

        if (! in_array($assignment->status, ['proposed', 'accepted'], true)) {
            abort(422, 'Задачу нельзя отклонить в текущем статусе.');
        }

        $oldStatus = $assignment->status;

        $assignment->update([
            'status' => 'declined',
        ]);

        $assignment->refresh();

        $this->dispatchAssignmentEvents($assignment, $oldStatus);

        return response()->json(['status' => 'ok']);
    }

    public function updateStatus(UpdateJobStatusRequest $request, HandymanAssignment $assignment)
    {
        $this->authorizeAssignment($request, $assignment);

        $status = $request->input('status'); // in_route, started, finished

        $oldStatus = $assignment->status;

        return DB::transaction(function () use ($assignment, $status, $oldStatus) {
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

            // При завершении — увеличим счётчик выполненных заказов мастера
            if ($status === 'finished') {
                $executor = $assignment->executorProfile;
                $executor->increment('completed_orders_count');
            }

            $assignment->refresh();

            $this->dispatchAssignmentEvents($assignment, $oldStatus);

            return response()->json(['status' => 'ok']);
        });
    }

    public function addMaterials(AddMaterialsRequest $request, HandymanAssignment $assignment)
    {
        $this->authorizeAssignment($request, $assignment);

        $order = $assignment->order;
        $executor = $assignment->executorProfile;

        $entry = HandymanMaterialsEntry::create([
            'order_id' => $order->id,
            'repair_project_id' => $order->repairProject?->id,
            'executor_profile_id' => $executor->id,
            'description' => $request->input('description'),
            'quantity' => $request->input('quantity'),
            'unit' => $request->input('unit'),
            'unit_price_minor' => $request->input('unit_price_minor'),
            'total_price_minor' => $request->input('total_price_minor'),
            'purchased_at' => $request->input('purchased_at') ? now()->parse($request->input('purchased_at')) : null,
            'receipt_url' => $request->input('receipt_url'),
            'meta' => $request->input('meta'),
        ]);

        return response()->json([
            'status' => 'ok',
            'material_id' => $entry->id,
        ]);
    }

    protected function authorizeAssignment(Request $request, HandymanAssignment $assignment): void
    {
        $profile = $request->user()->executorProfile;

        if (! $profile || $assignment->executor_profile_id !== $profile->id) {
            abort(403, 'Нет доступа к этой задаче.');
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
