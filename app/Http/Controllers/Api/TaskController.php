<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'status' => 'sometimes|string|in:pending,confirmed,in_progress,completed,cancelled',
            'assignee' => 'sometimes|integer|exists:users,id',
            'zone' => 'sometimes|integer|exists:geo_zones,id',
            'date' => 'sometimes|date',
        ]);

        $q = Task::query();
        if ($s = $request->get('status')) {
            $q->where('status', $s);
        }
        if ($a = $request->get('assignee')) {
            $q->where('assignee_id', $a);
        }
        if ($z = $request->get('zone')) {
            $q->where('zone_id', $z);
        }
        if ($date = $request->get('date')) {
            $q->whereDate('window_start', '<=', $date)->whereDate('window_end', '>=', $date);
        }

        return response()->json(['success' => true, 'data' => $q->orderByDesc('id')->paginate(50)]);
    }

    public function store(Request $request)
    {
        $v = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'type' => 'required|string',
            'status' => 'sometimes|string',
            'priority' => 'sometimes|string',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        $task = Task::create($request->only(['order_id', 'type', 'status', 'priority', 'assignee_id', 'zone_id', 'lat', 'lng']));

        return response()->json(['success' => true, 'data' => $task], 201);
    }

    public function update(string $id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'sometimes|string',
            'status' => 'sometimes|string|in:pending,confirmed,in_progress,completed,cancelled',
            'priority' => 'sometimes|string|in:low,normal,high,urgent',
            'assignee_id' => 'sometimes|integer|exists:users,id',
            'zone_id' => 'sometimes|integer|exists:geo_zones,id',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $task = Task::findOrFail($id);
        $task->fill($request->only(['type', 'status', 'priority', 'assignee_id', 'zone_id', 'lat', 'lng']));
        $task->save();

        return response()->json(['success' => true, 'data' => $task]);
    }

    public function storeEvent(string $id, Request $request)
    {
        $task = Task::findOrFail($id);
        $v = Validator::make($request->all(), [
            'to_status' => 'required|string',
            'reason' => 'nullable|string',
            'payload' => 'array',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        $event = TaskEvent::create([
            'task_id' => $task->id,
            'from_status' => $task->status,
            'to_status' => $request->to_status,
            'reason' => $request->reason,
            'payload' => $request->payload,
        ]);
        $task->status = $request->to_status;
        $task->save();

        return response()->json(['success' => true, 'data' => $event], 201);
    }
}
