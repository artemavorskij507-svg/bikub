<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\SocialCare\CareAccountReadService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SocialCareController extends Controller
{
    public function dashboard(
        Request $request,
        CareAccountReadService $careRead
    ): View {
        $user = $request->user();

        $clients = $careRead->getClientsForUser($user);

        if ($clients->isEmpty()) {
            abort(404, 'У вас пока нет доступа к социальной помощи');
        }

        return view('account.care.dashboard', [
            'clients' => $clients,
            'upcomingVisits' => $careRead->getUpcomingVisitsForUser($user, 10),
            'recentReports' => $careRead->getRecentReportsForUser($user, 10),
        ]);
    }

    public function showVisit(
        Request $request,
        Order $order,
        CareAccountReadService $careRead
    ): View {
        $user = $request->user();

        if (! $careRead->userCanAccessCareOrder($user, $order)) {
            abort(403, 'У вас нет доступа к этому визиту');
        }

        $order->load([
            'careDetails.clientProfile',
            'careDetails.trustedContact',
            'careDetails.careService',
            'careDetails.assignedHelper.user',
            'careDetails.visitReports.helperProfile',
            'parentOrder',
            'subOrders',
        ]);

        return view('account.care.visit-show', compact('order'));
    }
}
