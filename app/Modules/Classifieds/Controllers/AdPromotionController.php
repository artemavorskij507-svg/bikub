<?php

namespace App\Modules\Classifieds\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Classifieds\Models\ClassifiedAd;
use App\Modules\Classifieds\Services\AdPromotionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdPromotionController extends Controller
{
    protected array $pricing = [
        'bump' => 10,
        'highlight' => 20,
        'top' => 40,
        'vip' => 80,
    ];

    public function calculatePrice(Request $request)
    {
        $data = $request->validate([
            'service_type' => 'required|string|in:bump,highlight,top,vip',
            'days' => 'nullable|integer|min:1',
        ]);

        $type = $data['service_type'];
        $days = (int) ($data['days'] ?? 1);

        $price = $this->pricing[$type] * ($type === 'bump' ? 1 : $days);

        return response()->json([
            'service_type' => $type,
            'days' => $days,
            'price' => $price,
            'currency' => 'NOK',
        ]);
    }

    public function purchase(
        Request $request,
        ClassifiedAd $ad,
        AdPromotionService $service
    ) {
        if ($ad->user_id !== Auth::id()) {
            abort(403, 'You can only promote your own ads.');
        }

        $data = $request->validate([
            'service_type' => 'required|string|in:bump,highlight,top,vip',
            'days' => 'nullable|integer|min:1',
        ]);

        $type = $data['service_type'];
        $days = (int) ($data['days'] ?? 1);

        $price = $this->pricing[$type] * ($type === 'bump' ? 1 : $days);

        // TODO: интегрировать Stripe/Vipps. Сейчас считаем, что оплата прошла.
        match ($type) {
            'bump' => $service->bump($ad, $price),
            'highlight' => $service->highlight($ad, $days, $price),
            'top' => $service->top($ad, $days, $price),
            'vip' => $service->vip($ad, $days, $price),
        };

        $ad->refresh();

        return response()->json([
            'success' => true,
            'message' => "Promotion '{$type}' applied successfully.",
            'applied' => [
                'type' => $type,
                'amount' => $price,
                'expires_at' => match ($type) {
                    'bump' => null,
                    'highlight' => $ad->highlight_expires_at,
                    'top' => $ad->top_expires_at,
                    'vip' => $ad->vip_expires_at,
                },
            ],
        ]);
    }
}
