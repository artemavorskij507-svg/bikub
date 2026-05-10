<?php

namespace App\Http\Controllers\Account;

use App\Enums\ServiceType;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderReview;
use App\Services\Account\AccountReadService;
use App\Services\Handyman\HandymanKpiService;
use Illuminate\Http\Request;

class OrderReviewController extends Controller
{
    public function create(Request $request, Order $order, AccountReadService $accountRead)
    {
        $user = $request->user();

        if (! $accountRead->userCanAccessOrder($user, $order)) {
            abort(403, 'Нет доступа к заказу');
        }

        if ($order->review || $order->status !== 'completed' || ! $this->supportsReview($order)) {
            return redirect()
                ->route('account.orders.show', $order)
                ->with('error', 'Этот заказ нельзя оценить.');
        }

        return view('account.orders.review', [
            'order' => $order,
        ]);
    }

    public function store(
        Request $request,
        Order $order,
        AccountReadService $accountRead,
        HandymanKpiService $kpiService
    ) {
        $user = $request->user();

        if (! $accountRead->userCanAccessOrder($user, $order)) {
            abort(403, 'Нет доступа к заказу');
        }

        if ($order->review || $order->status !== 'completed' || ! $this->supportsReview($order)) {
            return redirect()
                ->route('account.orders.show', $order)
                ->with('error', 'Этот заказ нельзя оценить.');
        }

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $primaryAssignment = $order->primaryHandymanAssignment()
            ->with('executorProfile')
            ->first();

        $review = OrderReview::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'executor_profile_id' => $primaryAssignment?->executor_profile_id,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);

        if ($primaryAssignment?->executorProfile) {
            $kpiService->recalculateForExecutor($primaryAssignment->executorProfile);
        }

        return redirect()
            ->route('account.orders.show', $order)
            ->with('status', 'Спасибо за отзыв! Мы учтём его в рейтинге мастера.');
    }

    protected function supportsReview(Order $order): bool
    {
        return in_array($order->service_type, [
            ServiceType::HANDYMAN_HOURLY->value,
            ServiceType::HANDYMAN_FIXED->value,
            ServiceType::COMPLEX_REPAIR->value,
        ], true);
    }
}
