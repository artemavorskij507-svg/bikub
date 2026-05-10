<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GdprRequest;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GdprController extends Controller
{
    public function requestDataExport(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|uuid',
            'org_id' => 'sometimes|uuid',
            'description' => 'sometimes|string|max:1000',
        ]);

        $userId = $request->input('user_id');
        $orgId = $request->input('org_id');
        $description = $request->input('description', 'Data export request');

        // Check if user exists
        $user = User::find($userId);
        if (! $user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Create GDPR request
        $gdprRequest = GdprRequest::create([
            'user_id' => $userId,
            'org_id' => $orgId,
            'type' => 'export',
            'status' => 'pending',
            'description' => $description,
        ]);

        // Queue the export job
        dispatch(function () use ($gdprRequest, $user, $orgId) {
            $this->processDataExport($gdprRequest, $user, $orgId);
        })->delay(now()->addMinutes(1));

        return response()->json([
            'message' => 'Data export request submitted',
            'request_id' => $gdprRequest->id,
            'estimated_completion' => now()->addHours(24)->toISOString(),
        ]);
    }

    public function requestDataErasure(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|uuid',
            'org_id' => 'sometimes|uuid',
            'description' => 'sometimes|string|max:1000',
        ]);

        $userId = $request->input('user_id');
        $orgId = $request->input('org_id');
        $description = $request->input('description', 'Data erasure request');

        // Check if user exists
        $user = User::find($userId);
        if (! $user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Create GDPR request
        $gdprRequest = GdprRequest::create([
            'user_id' => $userId,
            'org_id' => $orgId,
            'type' => 'erase',
            'status' => 'pending',
            'description' => $description,
        ]);

        // Queue the erasure job
        dispatch(function () use ($gdprRequest, $user, $orgId) {
            $this->processDataErasure($gdprRequest, $user, $orgId);
        })->delay(now()->addMinutes(1));

        return response()->json([
            'message' => 'Data erasure request submitted',
            'request_id' => $gdprRequest->id,
            'estimated_completion' => now()->addHours(48)->toISOString(),
        ]);
    }

    public function requestDataRectification(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|uuid',
            'org_id' => 'sometimes|uuid',
            'description' => 'required|string|max:1000',
            'corrections' => 'required|array',
        ]);

        $userId = $request->input('user_id');
        $orgId = $request->input('org_id');
        $description = $request->input('description');
        $corrections = $request->input('corrections');

        // Check if user exists
        $user = User::find($userId);
        if (! $user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Create GDPR request
        $gdprRequest = GdprRequest::create([
            'user_id' => $userId,
            'org_id' => $orgId,
            'type' => 'rectify',
            'status' => 'pending',
            'description' => $description,
            'metadata' => ['corrections' => $corrections],
        ]);

        // Queue the rectification job
        dispatch(function () use ($gdprRequest, $user, $orgId, $corrections) {
            $this->processDataRectification($gdprRequest, $user, $orgId, $corrections);
        })->delay(now()->addMinutes(1));

        return response()->json([
            'message' => 'Data rectification request submitted',
            'request_id' => $gdprRequest->id,
            'estimated_completion' => now()->addHours(12)->toISOString(),
        ]);
    }

    public function getRequestStatus(Request $request, string $requestId): JsonResponse
    {
        $gdprRequest = GdprRequest::find($requestId);

        if (! $gdprRequest) {
            return response()->json(['error' => 'Request not found'], 404);
        }

        return response()->json([
            'request_id' => $gdprRequest->id,
            'type' => $gdprRequest->type,
            'status' => $gdprRequest->status,
            'description' => $gdprRequest->description,
            'created_at' => $gdprRequest->created_at,
            'resolved_at' => $gdprRequest->resolved_at,
            'result_url' => $gdprRequest->result_url,
            'metadata' => $gdprRequest->metadata,
        ]);
    }

    public function downloadExport(Request $request, string $requestId): JsonResponse
    {
        $gdprRequest = GdprRequest::find($requestId);

        if (! $gdprRequest) {
            return response()->json(['error' => 'Request not found'], 404);
        }

        if ($gdprRequest->type !== 'export' || $gdprRequest->status !== 'completed') {
            return response()->json(['error' => 'Export not available'], 400);
        }

        if (! $gdprRequest->result_url) {
            return response()->json(['error' => 'Export file not found'], 404);
        }

        // Generate temporary download URL
        $downloadUrl = Storage::disk('s3')->temporaryUrl(
            $gdprRequest->result_url,
            now()->addMinutes(30)
        );

        return response()->json([
            'download_url' => $downloadUrl,
            'expires_at' => now()->addMinutes(30)->toISOString(),
        ]);
    }

    private function processDataExport(GdprRequest $gdprRequest, User $user, ?string $orgId): void
    {
        try {
            $gdprRequest->update(['status' => 'processing']);

            // Collect all user data
            $userData = $this->collectUserData($user, $orgId);

            // Generate export file
            $filename = "gdpr_export_{$user->id}_{$gdprRequest->id}.json";
            $filePath = "gdpr-exports/{$filename}";

            // Store the export
            Storage::disk('s3')->put($filePath, json_encode($userData, JSON_PRETTY_PRINT));

            // Update request with result
            $gdprRequest->update([
                'status' => 'completed',
                'result_url' => $filePath,
                'resolved_at' => now(),
            ]);

        } catch (\Exception $e) {
            $gdprRequest->update([
                'status' => 'failed',
                'metadata' => array_merge($gdprRequest->metadata ?? [], [
                    'error' => $e->getMessage(),
                ]),
            ]);
        }
    }

    private function processDataErasure(GdprRequest $gdprRequest, User $user, ?string $orgId): void
    {
        try {
            $gdprRequest->update(['status' => 'processing']);

            DB::transaction(function () use ($user, $orgId) {
                // Anonymize user data instead of deleting (for audit purposes)
                $user->update([
                    'name' => 'ANONYMIZED',
                    'email' => "anonymized_{$user->id}@deleted.local",
                    'phone' => null,
                    'address' => null,
                ]);

                // Anonymize orders
                Order::where('user_id', $user->id)
                    ->when($orgId, function ($query) use ($orgId) {
                        return $query->where('org_id', $orgId);
                    })
                    ->update([
                        'customer_name' => 'ANONYMIZED',
                        'customer_phone' => null,
                        'customer_email' => "anonymized_{$user->id}@deleted.local",
                        'delivery_address' => 'ANONYMIZED',
                    ]);

                // Anonymize other related data
                $this->anonymizeRelatedData($user->id, $orgId);
            });

            $gdprRequest->update([
                'status' => 'completed',
                'resolved_at' => now(),
            ]);

        } catch (\Exception $e) {
            $gdprRequest->update([
                'status' => 'failed',
                'metadata' => array_merge($gdprRequest->metadata ?? [], [
                    'error' => $e->getMessage(),
                ]),
            ]);
        }
    }

    private function processDataRectification(GdprRequest $gdprRequest, User $user, ?string $orgId, array $corrections): void
    {
        try {
            $gdprRequest->update(['status' => 'processing']);

            DB::transaction(function () use ($user, $orgId, $corrections) {
                // Apply corrections to user data
                if (isset($corrections['name'])) {
                    $user->update(['name' => $corrections['name']]);
                }

                if (isset($corrections['email'])) {
                    $user->update(['email' => $corrections['email']]);
                }

                if (isset($corrections['phone'])) {
                    $user->update(['phone' => $corrections['phone']]);
                }

                // Apply corrections to related data
                $this->applyCorrectionsToRelatedData($user->id, $orgId, $corrections);
            });

            $gdprRequest->update([
                'status' => 'completed',
                'resolved_at' => now(),
            ]);

        } catch (\Exception $e) {
            $gdprRequest->update([
                'status' => 'failed',
                'metadata' => array_merge($gdprRequest->metadata ?? [], [
                    'error' => $e->getMessage(),
                ]),
            ]);
        }
    }

    private function collectUserData(User $user, ?string $orgId): array
    {
        $data = [
            'user' => $user->toArray(),
            'orders' => [],
            'subscriptions' => [],
            'reviews' => [],
            'loyalty_data' => [],
            'exported_at' => now()->toISOString(),
        ];

        // Get orders
        $ordersQuery = Order::where('user_id', $user->id);
        if ($orgId) {
            $ordersQuery->where('org_id', $orgId);
        }
        $data['orders'] = $ordersQuery->get()->toArray();

        // Get subscriptions
        if (class_exists('App\Models\Subscription')) {
            $subscriptionsQuery = \App\Models\Subscription::where('user_id', $user->id);
            if ($orgId) {
                $subscriptionsQuery->where('org_id', $orgId);
            }
            $data['subscriptions'] = $subscriptionsQuery->get()->toArray();
        }

        // Get reviews
        if (class_exists('App\Models\Review')) {
            $reviewsQuery = \App\Models\Review::where('user_id', $user->id);
            if ($orgId) {
                $reviewsQuery->where('org_id', $orgId);
            }
            $data['reviews'] = $reviewsQuery->get()->toArray();
        }

        // Get loyalty data
        if (class_exists('App\Models\LoyaltyWallet')) {
            $wallet = \App\Models\LoyaltyWallet::where('user_id', $user->id)->first();
            if ($wallet) {
                $data['loyalty_data'] = [
                    'wallet' => $wallet->toArray(),
                    'transactions' => \App\Models\LoyaltyLedger::where('wallet_id', $wallet->id)->get()->toArray(),
                ];
            }
        }

        return $data;
    }

    private function anonymizeRelatedData(string $userId, ?string $orgId): void
    {
        // Anonymize reviews
        if (class_exists('App\Models\Review')) {
            \App\Models\Review::where('user_id', $userId)
                ->when($orgId, function ($query) use ($orgId) {
                    return $query->where('org_id', $orgId);
                })
                ->update(['text' => 'ANONYMIZED']);
        }

        // Anonymize disputes
        if (class_exists('App\Models\Dispute')) {
            \App\Models\Dispute::where('user_id', $userId)
                ->when($orgId, function ($query) use ($orgId) {
                    return $query->where('org_id', $orgId);
                })
                ->update(['description' => 'ANONYMIZED']);
        }
    }

    private function applyCorrectionsToRelatedData(string $userId, ?string $orgId, array $corrections): void
    {
        // Apply corrections to orders
        if (isset($corrections['name'])) {
            Order::where('user_id', $userId)
                ->when($orgId, function ($query) use ($orgId) {
                    return $query->where('org_id', $orgId);
                })
                ->update(['customer_name' => $corrections['name']]);
        }

        if (isset($corrections['phone'])) {
            Order::where('user_id', $userId)
                ->when($orgId, function ($query) use ($orgId) {
                    return $query->where('org_id', $orgId);
                })
                ->update(['customer_phone' => $corrections['phone']]);
        }

        if (isset($corrections['email'])) {
            Order::where('user_id', $userId)
                ->when($orgId, function ($query) use ($orgId) {
                    return $query->where('org_id', $orgId);
                })
                ->update(['customer_email' => $corrections['email']]);
        }
    }
}
