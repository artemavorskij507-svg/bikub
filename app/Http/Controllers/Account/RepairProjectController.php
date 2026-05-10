<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\RepairProject;
use Illuminate\Http\Request;

class RepairProjectController extends Controller
{
    public function show(Request $request, RepairProject $project)
    {
        $user = $request->user();
        $order = $project->order;

        if (! $order || $order->user_id !== $user->id) {
            abort(403);
        }

        $project->load([
            'stages' => fn ($query) => $query->orderBy('sequence'),
            'media' => fn ($query) => $query->orderBy('created_at'),
            'updates' => fn ($query) => $query->orderByDesc('created_at')->limit(50),
            'projectManager.user',
        ]);

        return view('account.repairs.show', [
            'project' => $project,
            'order' => $order,
        ]);
    }
}
