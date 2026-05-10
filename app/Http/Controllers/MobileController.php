<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\GeoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MobileController extends Controller
{
    protected GeoService $geoService;

    public function __construct(GeoService $geoService)
    {
        $this->geoService = $geoService;
    }

    /**
     * Get courier tasks.
     */
    public function getTasks(Request $request)
    {
        $user = $request->user();

        $tasks = Task::where('assignee_id', $user->id)
            ->whereIn('status', ['queued', 'assigned', 'enroute'])
            ->with(['orderItem.order', 'orderItem.serviceType'])
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $tasks->map(function ($task) {
                return [
                    'id' => $task->id,
                    'order_number' => $task->order->order_number,
                    'service_name' => $task->orderItem->serviceType->name,
                    'priority' => $task->priority,
                    'status' => $task->status,
                    'checklist' => $task->checklist ?? [],
                    'notes' => $task->notes,
                    'assigned_at' => $task->assigned_at,
                    'eta' => $task->order->scheduled_at,
                    'address' => $task->order->address ?? 'Address not available',
                    'customer_phone' => $task->order->user->phone ?? 'Phone not available',
                    'customer_name' => $task->order->user->name ?? 'Name not available',
                ];
            }),
        ]);
    }

    /**
     * Check in to task with location.
     */
    public function checkIn(Request $request, string $taskId)
    {
        $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'note' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $task = Task::where('id', $taskId)
            ->where('assignee_id', $user->id)
            ->firstOrFail();

        try {
            $location = [
                'lat' => $request->lat,
                'lng' => $request->lng,
                'accuracy' => $request->accuracy ?? null,
                'timestamp' => now()->toISOString(),
            ];

            $task->checkIn($location, $request->note);

            return response()->json([
                'success' => true,
                'message' => 'Check-in successful',
                'data' => [
                    'task_id' => $task->id,
                    'status' => $task->status,
                    'checkin_at' => $task->checkin_at,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Mobile check-in failed', [
                'task_id' => $taskId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Check-in failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload proof files (photos, signatures).
     */
    public function uploadProofs(Request $request, string $taskId)
    {
        $request->validate([
            'files' => 'required|array|min:1|max:10',
            'files.*' => 'file|mimes:jpg,jpeg,png,pdf|max:10240', // 10MB max
            'type' => 'required|in:photo,signature,delivery_proof,damage_report',
            'note' => 'nullable|string|max:500',
        ]);

        $user = $request->user();
        $task = Task::where('id', $taskId)
            ->where('assignee_id', $user->id)
            ->firstOrFail();

        try {
            $uploadedFiles = [];

            foreach ($request->file('files') as $file) {
                $filename = 'proof_'.$task->id.'_'.time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
                $path = $file->storeAs('proofs', $filename);

                $uploadedFiles[] = [
                    'filename' => $filename,
                    'path' => $path,
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                ];
            }

            $proofData = [
                'type' => $request->type,
                'files' => $uploadedFiles,
                'note' => $request->note,
                'uploaded_at' => now()->toISOString(),
            ];

            $task->addProof($request->type, $proofData);

            return response()->json([
                'success' => true,
                'message' => 'Proofs uploaded successfully',
                'data' => [
                    'task_id' => $task->id,
                    'uploaded_files' => count($uploadedFiles),
                    'proof_type' => $request->type,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Mobile proof upload failed', [
                'task_id' => $taskId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Proof upload failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Complete task with checklist and final proof.
     */
    public function completeTask(Request $request, string $taskId)
    {
        $request->validate([
            'checklist' => 'required|array',
            'checklist.*.id' => 'required|string',
            'checklist.*.completed' => 'required|boolean',
            'checklist.*.note' => 'nullable|string|max:500',
            'final_note' => 'nullable|string|max:1000',
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        $user = $request->user();
        $task = Task::where('id', $taskId)
            ->where('assignee_id', $user->id)
            ->firstOrFail();

        try {
            DB::beginTransaction();

            // Update checklist items
            foreach ($request->checklist as $item) {
                $task->updateChecklistItem(
                    $item['id'],
                    $item['completed'],
                    $item['note'] ?? null
                );
            }

            // Check out with location
            $location = [
                'lat' => $request->lat,
                'lng' => $request->lng,
                'timestamp' => now()->toISOString(),
            ];

            $task->checkOut($location, $request->final_note);

            // Calculate AHT (Average Handling Time)
            $aht = $this->calculateAHT($task);

            // Update task with AHT
            $task->update([
                'notes' => ($task->notes ?? '')."\nAHT: {$aht} minutes",
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Task completed successfully',
                'data' => [
                    'task_id' => $task->id,
                    'status' => $task->status,
                    'completed_at' => $task->completed_at,
                    'aht_minutes' => $aht,
                    'checklist_completion' => $task->getChecklistCompletionPercentage(),
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Mobile task completion failed', [
                'task_id' => $taskId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Task completion failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Register device for PWA.
     */
    public function registerDevice(Request $request)
    {
        $request->validate([
            'device_id' => 'required|string|max:128',
            'fcm_token' => 'nullable|string',
            'device_info' => 'nullable|array',
        ]);

        $user = $request->user();

        try {
            $device = UserDevice::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'device_id' => $request->device_id,
                ],
                [
                    'fcm_token' => $request->fcm_token,
                    'meta' => $request->device_info ?? [],
                    'is_active' => true,
                    'last_seen_at' => now(),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Device registered successfully',
                'data' => [
                    'device_id' => $device->device_id,
                    'registered_at' => $device->created_at,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Device registration failed', [
                'user_id' => $user->id,
                'device_id' => $request->device_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Device registration failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get offline data for PWA.
     */
    public function getOfflineData(Request $request)
    {
        $user = $request->user();

        // Get tasks that might be needed offline
        $tasks = Task::where('assignee_id', $user->id)
            ->whereIn('status', ['queued', 'assigned', 'enroute'])
            ->with(['orderItem.order', 'orderItem.serviceType'])
            ->get();

        // Get service types for reference
        $serviceTypes = \App\Models\ServiceType::select('id', 'name', 'code', 'description')
            ->where('is_active', true)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'tasks' => $tasks->map(function ($task) {
                    return [
                        'id' => $task->id,
                        'order_number' => $task->order->order_number,
                        'service_name' => $task->orderItem->serviceType->name,
                        'priority' => $task->priority,
                        'status' => $task->status,
                        'checklist' => $task->checklist ?? [],
                        'notes' => $task->notes,
                        'assigned_at' => $task->assigned_at,
                        'eta' => $task->order->scheduled_at,
                        'address' => $task->order->address ?? 'Address not available',
                    ];
                }),
                'service_types' => $serviceTypes,
                'last_updated' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Sync offline data back to server.
     */
    public function syncOfflineData(Request $request)
    {
        $request->validate([
            'actions' => 'required|array',
            'actions.*.type' => 'required|in:checkin,checkout,proof,complete',
            'actions.*.task_id' => 'required|string',
            'actions.*.data' => 'required|array',
            'actions.*.timestamp' => 'required|string',
        ]);

        $user = $request->user();
        $results = [];

        try {
            DB::beginTransaction();

            foreach ($request->actions as $action) {
                $result = $this->processOfflineAction($user, $action);
                $results[] = $result;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Offline data synced successfully',
                'data' => [
                    'processed_actions' => count($results),
                    'results' => $results,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Offline sync failed', [
                'user_id' => $user->id,
                'actions_count' => count($request->actions),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Offline sync failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process individual offline action.
     */
    private function processOfflineAction(User $user, array $action): array
    {
        $task = Task::where('id', $action['task_id'])
            ->where('assignee_id', $user->id)
            ->firstOrFail();

        switch ($action['type']) {
            case 'checkin':
                $task->checkIn($action['data']['location'], $action['data']['note'] ?? null);
                break;

            case 'checkout':
                $task->checkOut($action['data']['location'], $action['data']['note'] ?? null);
                break;

            case 'proof':
                $task->addProof($action['data']['type'], $action['data']);
                break;

            case 'complete':
                foreach ($action['data']['checklist'] as $item) {
                    $task->updateChecklistItem($item['id'], $item['completed'], $item['note'] ?? null);
                }
                $task->checkOut($action['data']['location'], $action['data']['final_note'] ?? null);
                break;
        }

        return [
            'action_id' => $action['task_id'].'_'.$action['type'].'_'.$action['timestamp'],
            'status' => 'processed',
            'task_id' => $task->id,
            'task_status' => $task->status,
        ];
    }

    /**
     * Calculate Average Handling Time (AHT).
     */
    private function calculateAHT(Task $task): int
    {
        if (! $task->assigned_at || ! $task->completed_at) {
            return 0;
        }

        $startTime = $task->assigned_at;
        $endTime = $task->completed_at;

        return $endTime->diffInMinutes($startTime);
    }
}
