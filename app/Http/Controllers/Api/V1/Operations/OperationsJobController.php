<?php

namespace App\Http\Controllers\Api\V1\Operations;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Task;
use App\Services\Operations\ServiceJobNormalizer;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OperationsJobController extends Controller
{
    public function __construct(private readonly ServiceJobNormalizer $normalizer) {}

    public function normalize(Request $request)
    {
        $data = $request->validate([
            'source_type' => 'required|in:order,task',
            'source_id' => 'required|integer',
            'context' => 'nullable|array',
        ]);

        $context = $data['context'] ?? [];
        if ($data['source_type'] === 'order') {
            $order = Order::find($data['source_id']);
            if (! $order) {
                throw ValidationException::withMessages(['source_id' => 'Order not found']);
            }
            $job = $this->normalizer->normalizeFromOrder($order, $context);
        } else {
            $task = Task::find($data['source_id']);
            if (! $task) {
                throw ValidationException::withMessages(['source_id' => 'Task not found']);
            }
            $job = $this->normalizer->normalizeFromTask($task, $context);
        }

        return response()->json([
            'success' => true,
            'data' => $job->fresh(['order', 'task', 'slaPolicy', 'scheduleSlot']),
        ]);
    }
}

