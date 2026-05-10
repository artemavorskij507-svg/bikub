<?php

namespace App\Http\Controllers\Api\V1\Operations;

use App\Events\Operations\ExceptionOpened;
use App\Http\Controllers\Controller;
use App\Models\Operations\OperationException;
use App\Models\Operations\ServiceJob;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OperationsExceptionController extends Controller
{
    public function open(Request $request)
    {
        $data = $request->validate([
            'service_job_id' => 'required|integer',
            'assignment_id' => 'nullable|integer',
            'exception_type' => 'required|string|max:64',
            'severity' => 'nullable|in:low,medium,high,critical',
            'summary' => 'nullable|string|max:1000',
            'metadata' => 'nullable|array',
        ]);

        $job = ServiceJob::find($data['service_job_id']);
        if (! $job) {
            throw ValidationException::withMessages(['service_job_id' => 'ServiceJob not found']);
        }

        $exception = OperationException::create([
            'organization_id' => $job->organization_id,
            'service_job_id' => $job->id,
            'assignment_id' => $data['assignment_id'] ?? null,
            'exception_type' => $data['exception_type'],
            'severity' => $data['severity'] ?? 'medium',
            'status' => 'open',
            'detected_at' => now(),
            'summary' => $data['summary'] ?? null,
            'metadata' => $data['metadata'] ?? [],
        ]);

        event(new ExceptionOpened($exception));

        return response()->json([
            'success' => true,
            'data' => $exception->fresh(),
        ]);
    }

    public function resolve(Request $request, int $id)
    {
        $data = $request->validate([
            'note' => 'nullable|string|max:1000',
            'remediation' => 'nullable|array',
        ]);

        $exception = OperationException::find($id);
        if (! $exception) {
            throw ValidationException::withMessages(['exception' => 'Exception not found']);
        }

        $exception->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'summary' => $data['note'] ?? $exception->summary,
            'remediation' => $data['remediation'] ?? $exception->remediation,
        ]);

        return response()->json([
            'success' => true,
            'data' => $exception->fresh(),
        ]);
    }

    public function ack(Request $request, int $id)
    {
        $exception = OperationException::find($id);
        if (! $exception) {
            throw ValidationException::withMessages(['exception' => 'Exception not found']);
        }

        $exception->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
            'owner_user_id' => auth()->id() ?: $exception->owner_user_id,
        ]);

        return response()->json([
            'success' => true,
            'data' => $exception->fresh(),
        ]);
    }
}
