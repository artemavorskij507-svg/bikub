<?php

namespace App\Http\Controllers\Api\V1\Operations;

use App\Http\Controllers\Controller;
use App\Models\Operations\Assignment;
use App\Models\Operations\Executor;
use App\Models\Operations\OperationException;
use App\Models\Operations\ServiceJob;
use App\Services\Operations\LiveOpsStateService;
use Illuminate\Http\Request;

class OpsController extends Controller
{
    public function jobs(Request $request)
    {
        $query = ServiceJob::query()->with(['activeAssignment.executor', 'slaTimer']);
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('service_domain')) {
            $query->where('service_domain', $request->string('service_domain'));
        }

        return response()->json([
            'success' => true,
            'data' => $query->latest()->paginate(50),
        ]);
    }

    public function job(ServiceJob $job)
    {
        return response()->json([
            'success' => true,
            'data' => $job->load(['assignments.executor', 'slaTimer', 'exceptions']),
        ]);
    }

    public function jobTimeline(ServiceJob $job)
    {
        return response()->json([
            'success' => true,
            'data' => $job->timeline()->paginate(100),
        ]);
    }

    public function executors(Request $request)
    {
        $query = Executor::query()->with(['skills', 'locations']);
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json([
            'success' => true,
            'data' => $query->latest()->paginate(100),
        ]);
    }

    public function assignments(Request $request)
    {
        $query = Assignment::query()->with(['serviceJob', 'executor']);
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json([
            'success' => true,
            'data' => $query->latest()->paginate(100),
        ]);
    }

    public function exceptions(Request $request)
    {
        $query = OperationException::query()->with(['serviceJob', 'assignment']);
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json([
            'success' => true,
            'data' => $query->latest()->paginate(100),
        ]);
    }

    public function liveMap(Request $request, LiveOpsStateService $service)
    {
        return response()->json([
            'success' => true,
            'data' => $service->getState($request->only(['organization_id', 'service_domain', 'status'])),
        ]);
    }
}

