<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $summary = PaymentTransaction::where('user_id', $user->id)
            ->selectRaw("
                COALESCE(SUM(CASE WHEN type = 'charge' THEN amount_minor ELSE 0 END), 0) as total_charged,
                COALESCE(SUM(CASE WHEN type = 'refund' THEN amount_minor ELSE 0 END), 0) as total_refunded,
                COALESCE(SUM(CASE WHEN type = 'tip' THEN amount_minor ELSE 0 END), 0) as total_tips,
                COALESCE(SUM(amount_minor), 0) as net_total
            ")
            ->first();

        $latestTransactions = PaymentTransaction::where('user_id', $user->id)
            ->with('order')
            ->latest('processed_at')
            ->limit(10)
            ->get();

        return view('account.billing.index', [
            'summary' => $summary,
            'latestTransactions' => $latestTransactions,
        ]);
    }

    public function transactions(Request $request): View
    {
        $user = $request->user();

        $type = $request->query('type');
        $serviceType = $request->query('service_type');

        $query = PaymentTransaction::query()
            ->where('user_id', $user->id)
            ->with('order')
            ->latest('processed_at');

        if ($type) {
            $query->where('type', $type);
        }

        if ($serviceType) {
            $query->whereHas('order', function ($q) use ($serviceType) {
                $q->where('service_type', $serviceType);
            });
        }

        $transactions = $query->paginate(25)->withQueryString();

        return view('account.billing.transactions', [
            'transactions' => $transactions,
            'currentType' => $type,
            'currentServiceType' => $serviceType,
        ]);
    }
}
