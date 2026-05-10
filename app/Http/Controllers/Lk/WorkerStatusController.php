<?php

namespace App\Http\Controllers\Lk;

use App\Http\Controllers\Controller;
use App\Models\WorkerStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkerStatusController extends Controller
{
    /**
     * Update worker online/offline status.
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'online' => 'required|boolean',
        ]);

        $workerStatus = WorkerStatus::firstOrCreate(
            ['user_id' => $user->id],
            ['is_online' => false]
        );

        $oldStatus = $workerStatus->is_online;
        $workerStatus->is_online = $request->boolean('online');
        $workerStatus->updated_at = now();
        $workerStatus->save();

        // Логирование для отладки
        \Log::info('WorkerStatus updated', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'user_roles' => $user->roles->pluck('name')->toArray(),
            'old_status' => $oldStatus,
            'new_status' => $workerStatus->is_online,
            'worker_status_id' => $workerStatus->id,
        ]);

        return response()->json([
            'success' => true,
            'is_online' => $workerStatus->is_online,
            'message' => $workerStatus->is_online
                ? 'Вы вышли на смену'
                : 'Вы завершили смену',
        ]);
    }

    /**
     * Toggle worker online/offline status.
     */
    public function toggle(Request $request): JsonResponse
    {
        $user = $request->user();

        $workerStatus = WorkerStatus::firstOrCreate(
            ['user_id' => $user->id],
            ['is_online' => false]
        );

        $oldStatus = $workerStatus->is_online;
        $workerStatus->is_online = ! $workerStatus->is_online;
        $workerStatus->updated_at = now();
        $workerStatus->save();

        // Логирование для отладки
        \Log::info('WorkerStatus toggled', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'user_roles' => $user->roles->pluck('name')->toArray(),
            'old_status' => $oldStatus,
            'new_status' => $workerStatus->is_online,
            'worker_status_id' => $workerStatus->id,
        ]);

        return response()->json([
            'success' => true,
            'is_online' => $workerStatus->is_online,
            'message' => $workerStatus->is_online
                ? 'Вы вышли на смену'
                : 'Вы завершили смену',
        ]);
    }
}
