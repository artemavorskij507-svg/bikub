<?php

namespace App\Http\Controllers\Account;

use App\Events\ClaimOpened;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\CreateClaimRequest;
use App\Models\Claim;
use App\Models\Order;
use App\Services\Account\AccountReadService;
use Illuminate\Http\Request;

class OrderClaimController extends Controller
{
    public function create(
        Request $request,
        Order $order,
        AccountReadService $accountRead
    ) {
        $user = $request->user();

        if (! $accountRead->userCanAccessOrder($user, $order)) {
            abort(403, 'У вас нет доступа к этому заказу');
        }

        return view('account.orders.claim', [
            'order' => $order,
        ]);
    }

    public function store(
        CreateClaimRequest $request,
        Order $order,
        AccountReadService $accountRead
    ) {
        $user = $request->user();

        if (! $accountRead->userCanAccessOrder($user, $order)) {
            abort(403, 'У вас нет доступа к этому заказу');
        }

        $claim = Claim::create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'repair_project_id' => $order->repairProject?->id,
            'opened_by_user_id' => $user->id,
            'assigned_to_user_id' => null,
            'type' => $request->input('type'),
            'status' => 'open',
            'severity' => $request->input('severity'),
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'opened_at' => now(),
        ]);

        event(new ClaimOpened($claim));

        return redirect()
            ->route('account.orders.show', $order)
            ->with('status', 'Претензия зарегистрирована. Мы вернёмся с ответом после проверки.');
    }
}
