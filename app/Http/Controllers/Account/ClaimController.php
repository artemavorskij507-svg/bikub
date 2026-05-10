<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Claim;
use Illuminate\Http\Request;

class ClaimController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $claims = Claim::query()
            ->where('user_id', $user->id)
            ->with(['order', 'repairProject', 'assignedTo', 'messages'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('account.claims.index', compact('claims'));
    }

    public function show(Request $request, Claim $claim)
    {
        $user = $request->user();

        if ($claim->user_id !== $user->id && ! $user->can('view', $claim)) {
            abort(403);
        }

        $claim->load([
            'order',
            'repairProject',
            'assignedTo',
            'messages.sender',
        ]);

        return view('account.claims.show', compact('claim'));
    }
}
