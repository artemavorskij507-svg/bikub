<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PriceEstimateRequest;
use App\Models\PriceEstimateLog;
use App\Services\Pricing\OrderContext;
use App\Services\Pricing\PriceEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class PriceEstimateController extends Controller
{
    public function __construct(
        protected PriceEngine $priceEngine
    ) {}

    public function __invoke(PriceEstimateRequest $request): JsonResponse
    {
        if (! config('feature_flags.enable_dynamic_pricing', false)) {
            abort(403, 'Dynamic pricing is disabled in this environment.');
        }

        $payload = $request->validated();
        $hash = $this->hashPayload($payload);

        $result = Cache::remember("pricing:estimate:{$hash}", 10, function () use ($payload, $request) {
            $context = OrderContext::fromArray([
                ...$payload,
                'zone' => $payload['zone'] ?? null,
                'user_id' => $request->user()?->id,
                'ip_address' => $request->ip(),
            ]);

            return $this->priceEngine->estimate($context);
        });

        PriceEstimateLog::create([
            'uuid' => Str::uuid()->toString(),
            'service_type' => $payload['service_type'],
            'zone' => $payload['zone'] ?? null,
            'currency' => $result->currency,
            'user_id' => $request->user()?->id,
            'request_hash' => $hash,
            'payload' => $payload,
            'result' => [
                'subtotal' => $result->subtotal,
                'total' => $result->total,
                'breakdown' => $result->breakdown,
            ],
            'subtotal' => $result->subtotal,
            'total' => $result->total,
            'duration_ms' => $result->durationMs,
            'ip_address' => $request->ip(),
        ]);

        app(\App\Services\Pricing\DemandService::class)->recordRequest($payload['zone'] ?? null);

        return response()->json([
            'id' => $hash,
            'subtotal' => $result->subtotal,
            'total' => $result->total,
            'currency' => $result->currency,
            'breakdown' => $result->breakdown,
            'duration_ms' => $result->durationMs,
        ]);
    }

    protected function hashPayload(array $payload): string
    {
        ksort($payload);

        return hash('sha256', json_encode($payload));
    }
}
