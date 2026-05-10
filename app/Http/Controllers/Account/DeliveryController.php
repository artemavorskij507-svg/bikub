<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Delivery\DeliveryOrder;
use App\Models\Restaurant;
use App\Models\RetailStore;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeliveryController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $deliveries = DeliveryOrder::query()
            ->with(['order'])
            ->whereHas('order', fn ($query) => $query->where('user_id', $user->id))
            ->latest()
            ->paginate(20);

        return view('account.deliveries.index', [
            'deliveries' => $deliveries,
        ]);
    }

    public function show(Request $request, DeliveryOrder $deliveryOrder): View
    {
        $deliveryOrder->load(['order', 'order.user', 'courier', 'orderable']);

        abort_if($deliveryOrder->order?->user_id !== $request->user()->id, 404);

        return view('account.deliveries.show', [
            'order' => $deliveryOrder->order,
            'deliveryOrder' => $deliveryOrder,
        ]);
    }

    public function create(Request $request): View
    {
        $stores = RetailStore::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $restaurants = Restaurant::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('account.deliveries.create', [
            'stores' => $stores,
            'restaurants' => $restaurants,
        ]);
    }
}
