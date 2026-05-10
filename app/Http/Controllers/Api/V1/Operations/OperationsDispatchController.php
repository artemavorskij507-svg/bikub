<?php

namespace App\Http\Controllers\Api\V1\Operations;

use App\Http\Controllers\Controller;
use App\Models\Operations\ServiceJob;
use App\Services\Operations\DispatchEngineService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OperationsDispatchController extends Controller
{
    public function __construct(private readonly DispatchEngineService $dispatchEngine) {}

    public function request(Request $request)
    {
        $data = $request->validate([
            'service_job_id' => 'required|integer',
            'mode' => 'nullable|in:auto_assign,dispatcher_approval,manual_override',
            'filters' => 'nullable|array',
        ]);

        $job = ServiceJob::find($data['service_job_id']);
        if (! $job) {
            throw ValidationException::withMessages(['service_job_id' => 'ServiceJob not found']);
        }

        $result = $this->dispatchEngine->requestDispatch(
            $job,
            $data['mode'] ?? 'auto_assign',
            $data['filters'] ?? []
        );

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    public function replan(Request $request)
    {
        $data = $request->validate([
            'service_job_id' => 'required|integer',
            'reason' => 'required|string|max:255',
            'context' => 'nullable|array',
        ]);

        $job = ServiceJob::find($data['service_job_id']);
        if (! $job) {
            throw ValidationException::withMessages(['service_job_id' => 'ServiceJob not found']);
        }

        $result = $this->dispatchEngine->replan($job, $data['reason'], $data['context'] ?? []);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}

