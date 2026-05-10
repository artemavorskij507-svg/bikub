<?php

namespace App\Support\Local;

use App\Enums\CareOrderStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SocialCareLocalDemoSeeder
{
    public static function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $requiredTables = [
            'users',
            'orders',
            'client_profiles',
            'care_services',
            'social_helper_profiles',
            'care_order_details',
        ];

        foreach ($requiredTables as $table) {
            if (! Schema::hasTable($table)) {
                return;
            }
        }

        $completedExists = DB::table('care_order_details')
            ->where('care_status', CareOrderStatus::COMPLETED->value)
            ->exists();

        $userIds = DB::table('users')->limit(6)->pluck('id')->filter()->values();
        if ($userIds->isEmpty()) {
            return;
        }

        $now = now();

        $helperIds = self::seedHelpers($userIds->all(), $now);
        $serviceIds = self::seedServices($now);
        $clientIds = self::seedClients($userIds->all(), $now);

        if (empty($helperIds) || empty($serviceIds) || empty($clientIds)) {
            return;
        }

        if (! $completedExists) {
            $activePlanId = self::seedActivePlan($clientIds[0], $serviceIds[0], $helperIds[0], $now);
            self::seedCompletedVisits($userIds->all(), $clientIds, $serviceIds, $helperIds, $activePlanId, $now);
        }

        self::seedCommunityPoints($helperIds, $now);
    }

    protected static function seedHelpers(array $userIds, $now): array
    {
        $levels = ['SOCIAL_HELPER', 'COMMUNITY_PARTNER', 'BIKUBE_FRIEND'];
        $helperIds = DB::table('social_helper_profiles')->pluck('id')->take(3)->values()->all();

        if (! empty($helperIds)) {
            return $helperIds;
        }

        foreach (array_slice($userIds, 0, 3) as $index => $userId) {
            DB::table('social_helper_profiles')->insert([
                'user_id' => $userId,
                'level' => $levels[$index % count($levels)],
                'display_name' => 'Local Helper '.($index + 1),
                'bio' => 'Local Social Care demo helper.',
                'skills' => json_encode(['companionship', 'daily_support', 'wellbeing']),
                'has_police_certificate' => true,
                'police_certificate_verified_at' => $now->copy()->subMonths(2),
                'rating_avg' => 4.8 - ($index * 0.1),
                'rating_count' => 7 + $index,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return DB::table('social_helper_profiles')->pluck('id')->take(3)->values()->all();
    }

    protected static function seedServices($now): array
    {
        $services = DB::table('care_services')->pluck('id')->take(3)->values()->all();
        if (! empty($services)) {
            return $services;
        }

        $rows = [
            [
                'code' => 'companionship_visit',
                'name' => 'Companionship Visit',
                'description' => 'Regular social and wellbeing visit.',
                'required_level' => 'COMMUNITY_PARTNER',
                'base_duration_minutes' => 60,
                'base_price_nok' => 0,
                'is_recurring_available' => true,
                'is_active' => true,
            ],
            [
                'code' => 'daily_support',
                'name' => 'Daily Support',
                'description' => 'Daily living support and routine check-in.',
                'required_level' => 'SOCIAL_HELPER',
                'base_duration_minutes' => 90,
                'base_price_nok' => 390,
                'is_recurring_available' => true,
                'is_active' => true,
            ],
            [
                'code' => 'medication_reminder',
                'name' => 'Medication Reminder',
                'description' => 'Reminder and supervision for medication routine.',
                'required_level' => 'BIKUBE_FRIEND',
                'base_duration_minutes' => 45,
                'base_price_nok' => 190,
                'is_recurring_available' => false,
                'is_active' => true,
            ],
        ];

        foreach ($rows as $row) {
            DB::table('care_services')->insert([
                ...$row,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return DB::table('care_services')->pluck('id')->take(3)->values()->all();
    }

    protected static function seedClients(array $userIds, $now): array
    {
        $clientIds = DB::table('client_profiles')->pluck('id')->take(3)->values()->all();
        if (! empty($clientIds)) {
            return $clientIds;
        }

        $cities = ['Oslo', 'Bergen', 'Trondheim'];
        $names = ['Ingrid Larsen', 'Ole Hansen', 'Marta Solberg'];

        for ($i = 0; $i < 3; $i++) {
            DB::table('client_profiles')->insert([
                'user_id' => $userIds[$i] ?? $userIds[0],
                'full_name' => $names[$i],
                'phone' => '+4791000'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'email' => 'socialcare.client.'.($i + 1).'@local.demo',
                'address_line' => 'Demo street '.($i + 1),
                'postal_code' => '01'.str_pad((string) (10 + $i), 2, '0', STR_PAD_LEFT),
                'city' => $cities[$i],
                'communication_preferences' => json_encode(['language' => 'no', 'channel' => 'phone']),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return DB::table('client_profiles')->pluck('id')->take(3)->values()->all();
    }

    protected static function seedActivePlan(int $clientId, int $serviceId, int $helperId, $now): ?int
    {
        if (! Schema::hasTable('care_plans')) {
            return null;
        }

        $existingPlanId = DB::table('care_plans')
            ->where('client_profile_id', $clientId)
            ->where('status', 'ACTIVE')
            ->value('id');

        if ($existingPlanId) {
            return (int) $existingPlanId;
        }

        return (int) DB::table('care_plans')->insertGetId([
            'client_profile_id' => $clientId,
            'care_service_id' => $serviceId,
            'service_type_code' => 'social_care_visit',
            'frequency' => 'WEEKLY',
            'day_of_week' => (int) $now->dayOfWeek,
            'time_of_day' => '10:00:00',
            'duration_minutes' => 60,
            'preferred_helper_level' => 'COMMUNITY_PARTNER',
            'preferred_helper_id' => $helperId,
            'starts_at' => $now->copy()->subWeeks(2),
            'status' => 'ACTIVE',
            'notes' => 'Local demo care plan.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    protected static function seedCompletedVisits(array $userIds, array $clientIds, array $serviceIds, array $helperIds, ?int $carePlanId, $now): void
    {
        $ownerUserId = (int) ($userIds[0] ?? 0);
        if ($ownerUserId <= 0) {
            return;
        }

        for ($i = 0; $i < 5; $i++) {
            $scheduledStart = $now->copy()->subDays(2 + ($i * 3))->setTime(10 + ($i % 3), 0);
            $scheduledEnd = $scheduledStart->copy()->addMinutes(60 + ($i * 15));

            $orderId = DB::table('orders')->insertGetId([
                'order_number' => 'SC-'.strtoupper(Str::random(10)),
                'user_id' => $ownerUserId,
                'service_type' => 'social_care_visit',
                'status' => 'completed',
                'priority' => 'normal',
                'notes' => 'Local demo social care order.',
                'scheduled_at' => $scheduledStart,
                'completed_at' => $scheduledEnd,
                'total_amount' => 0,
                'currency' => 'NOK',
                'payment_status' => 'paid',
                'metadata' => json_encode(['service_type' => 'social_care_visit', 'seed' => 'local_demo']),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $careOrderId = DB::table('care_order_details')->insertGetId([
                'order_id' => $orderId,
                'client_profile_id' => $clientIds[$i % count($clientIds)],
                'care_service_id' => $serviceIds[$i % count($serviceIds)],
                'care_plan_id' => $carePlanId,
                'care_status' => CareOrderStatus::COMPLETED->value,
                'scheduled_start_at' => $scheduledStart,
                'scheduled_end_at' => $scheduledEnd,
                'assigned_helper_id' => $helperIds[$i % count($helperIds)],
                'requested_helper_level' => 'COMMUNITY_PARTNER',
                'price_nok' => 0,
                'notes_for_helper' => 'Local demo visit.',
                'internal_notes' => 'Seeded for local analytics.',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if (Schema::hasTable('visit_reports')) {
                DB::table('visit_reports')->insert([
                    'care_order_details_id' => $careOrderId,
                    'helper_profile_id' => $helperIds[$i % count($helperIds)],
                    'started_at' => $scheduledStart,
                    'ended_at' => $scheduledEnd,
                    'status' => 'COMPLETED',
                    'summary' => 'Local demo visit report.',
                    'client_mood' => 'HAPPY',
                    'followup_recommended' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    protected static function seedCommunityPoints(array $helperIds, $now): void
    {
        if (! Schema::hasTable('community_points_balances')) {
            return;
        }

        $hasBalances = DB::table('community_points_balances')->exists();
        if (! $hasBalances) {
            foreach (array_slice($helperIds, 0, 3) as $index => $helperId) {
                DB::table('community_points_balances')->insert([
                    'helper_profile_id' => $helperId,
                    'balance_points' => 120 + ($index * 45),
                    'lifetime_points' => 220 + ($index * 75),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (! Schema::hasTable('community_points_transactions')) {
            return;
        }

        if (DB::table('community_points_transactions')->exists()) {
            return;
        }

        $reasonCodes = ['VISIT_COMPLETED', 'BONUS', 'ADJUSTMENT'];
        foreach (array_slice($helperIds, 0, 3) as $index => $helperId) {
            DB::table('community_points_transactions')->insert([
                'helper_profile_id' => $helperId,
                'delta_points' => 20 + ($index * 10),
                'reason_code' => $reasonCodes[$index % count($reasonCodes)],
                'meta' => json_encode(['source' => 'local_demo_seed']),
                'created_at' => $now->copy()->subDays(3 - $index),
            ]);
        }
    }
}
