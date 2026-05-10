<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountBillingApiController extends Controller
{
    public function summary(Request $request): JsonResponse
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

        return response()->json([
            'data' => [
                'currency' => 'NOK',
                'total_charged' => ($summary->total_charged ?? 0) / 100,
                'total_refunded' => ($summary->total_refunded ?? 0) / 100,
                'total_tips' => ($summary->total_tips ?? 0) / 100,
                'net_total' => ($summary->net_total ?? 0) / 100,
            ],
        ]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $user = $request->user();
        $type = $request->query('type');
        $serviceType = $request->query('service_type');

        $query = PaymentTransaction::where('user_id', $user->id)
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

        $paginator = $query->paginate(25);

        return response()->json([
            'data' => $paginator->getCollection()->map(function (PaymentTransaction $transaction) {
                return [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'amount' => $transaction->amount,
                    'currency' => $transaction->currency,
                    'status' => $transaction->status,
                    'label' => $transaction->label,
                    'order_id' => $transaction->order_id,
                    'service_type' => $transaction->order?->service_type,
                    'provider' => $transaction->provider,
                    'processed_at' => $transaction->processed_at,
                    'meta' => $transaction->meta,
                ];
            })->values()->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }
}
