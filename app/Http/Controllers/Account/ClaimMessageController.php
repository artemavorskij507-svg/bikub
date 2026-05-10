<?php

namespace App\Http\Controllers\Account;

use App\Events\ClaimMessageCreated;
use App\Http\Controllers\Controller;
use App\Models\Claim;
use App\Models\ClaimMessage;
use App\Services\Claims\ClaimSlaService;
use Illuminate\Http\Request;

class ClaimMessageController extends Controller
{
    public function store(Request $request, Claim $claim)
    {
        $user = $request->user();

        // Авторизация: клиент может писать только в свои претензии,
        // OPS-роль — по policy view/update
        if ($claim->user_id !== $user->id && ! $user->can('view', $claim)) {
            abort(403);
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $message = ClaimMessage::create([
            'claim_id' => $claim->id,
            'sender_id' => $user->id,
            'sender_role' => $user->roles()->first()?->name ?? null,
            'body' => $data['body'],
            'meta' => [],
        ]);

        // Помечаем, что был ответ (для SLA response)
        app(ClaimSlaService::class)->markResponded($claim);

        // Event для real-time и нотификаций / n8n
        event(new ClaimMessageCreated($message));

        return back();
    }
}
