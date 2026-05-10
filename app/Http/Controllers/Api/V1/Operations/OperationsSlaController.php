<?php

namespace App\Http\Controllers\Api\V1\Operations;

use App\Http\Controllers\Controller;
use App\Models\Operations\ServiceJob;
use App\Services\Operations\OperationsSlaService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OperationsSlaController extends Controller
{
    public function __construct(private readonly OperationsSlaService $slaService) {}

    public function evaluate(Request $request)
    {
        $data = $request->validate([
            'service_job_id' => 'required|integer',
        ]);

        $job = ServiceJob::find($data['service_job_id']);
        if (! $job) {
            throw ValidationException::withMessages(['service_job_id' => 'ServiceJob not found']);
        }

        $result = $this->slaService->evaluate($job);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}

