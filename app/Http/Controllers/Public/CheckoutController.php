<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Orders\OrderPricingCoordinator;
use App\Services\Orders\OrderScenarioRegistry;
use App\Services\Orders\UnifiedOrderEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function show(string $scenario, OrderScenarioRegistry $registry, OrderPricingCoordinator $pricing): View
    {
        $scenarioConfig = $registry->getEnabled($scenario);
        $estimate = $pricing->estimate($scenarioConfig, []);

        return view('public.checkout.show', [
            'scenario' => $scenarioConfig,
            'estimate' => $estimate,
        ]);
    }

    public function store(string $scenario, Request $request, UnifiedOrderEngine $engine): RedirectResponse
    {
        if (is_string($request->input('items'))) {
            $decoded = json_decode((string) $request->input('items'), true);
            if (is_array($decoded)) {
                $request->merge(['items' => $decoded]);
            }
        }

        $data = $request->validate([
            'ad_id' => ['nullable', 'integer'],
            'classified_ad_id' => ['nullable', 'integer'],
            'pickup_address' => ['nullable', 'string', 'max:500'],
            'delivery_address' => ['nullable', 'string', 'max:500'],
            'address' => ['nullable', 'string', 'max:500'],
            'delivery_window' => ['nullable', 'string', 'max:120'],
            'slot' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_urgent' => ['nullable', 'boolean'],
            'items' => ['nullable', 'array'],
            'items.*.name' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
            'items.*.price' => ['nullable', 'numeric', 'min:0'],
        ]);

        if (! empty($data['ad_id']) && empty($data['classified_ad_id'])) {
            $data['classified_ad_id'] = (int) $data['ad_id'];
        }
        if (! empty($data['classified_ad_id'])) {
            $data['source'] = 'classifieds';
        }

        $scenarioConfig = app(OrderScenarioRegistry::class)->getEnabled($scenario);
        $missing = app(OrderScenarioRegistry::class)->validateRequiredFields($scenarioConfig, $data);
        if ($missing !== []) {
            return back()
                ->withErrors(['scenario' => 'Missing required fields: '.implode(', ', $missing)])
                ->withInput();
        }

        $order = $engine->create($scenario, $request->user(), $data);

        return redirect()
            ->route('account.dashboard')
            ->with('status', "Заказ {$order->order_number} создан и передан в операционный центр.");
    }
}
