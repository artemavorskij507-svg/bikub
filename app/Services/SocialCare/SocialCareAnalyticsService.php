<?php

namespace App\Services\SocialCare;

use App\Enums\CareOrderStatus;
use App\Models\CareOrderDetails;
use App\Models\CarePlan;
use App\Models\ClientProfile;
use App\Models\SocialHelperProfile;
use App\Models\VisitReport;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SocialCareAnalyticsService
{
    /**
     * Get aggregated KPI for the period.
     */
    public function aggregateKpi(
        Carbon $from,
        Carbon $to,
        ?string $helperLevel = null,
        ?int $careServiceId = null,
        ?string $city = null
    ): array {
        $baseQuery = $this->getBaseVisitsQuery($from, $to, $helperLevel, $careServiceId, $city)
            ->where('care_status', CareOrderStatus::COMPLETED->value);

        // Total hours
        $totalHours = $this->calculateTotalHours($baseQuery);

        // Unique clients
        $uniqueClients = (clone $baseQuery)
            ->distinct('client_profile_id')
            ->count('client_profile_id');

        // Active care plans (at end of period)
        $activePlans = CarePlan::query()
            ->where('status', 'ACTIVE')
            ->where(function ($q) use ($to) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $to);
            })
            ->when($city, function ($q, $city) {
                $q->whereHas('clientProfile', fn ($sq) => $sq->where('city', $city));
            })
            ->count();

        // Total visits
        $totalVisits = (clone $baseQuery)->count();

        // Volunteer hours (Community Partner + Bikube Friend)
        $volunteerHours = $this->calculateVolunteerHours($from, $to, $helperLevel, $careServiceId, $city);

        return [
            'total_hours' => round($totalHours, 2),
            'unique_clients' => $uniqueClients,
            'active_care_plans' => $activePlans,
            'total_visits' => $totalVisits,
            'volunteer_hours' => round($volunteerHours, 2),
        ];
    }

    /**
     * Get visits and hours grouped by day.
     */
    public function visitsAndHoursByDay(
        Carbon $from,
        Carbon $to,
        ?string $helperLevel = null,
        ?int $careServiceId = null,
        ?string $city = null
    ): Collection {
        $baseQuery = $this->getBaseVisitsQuery($from, $to, $helperLevel, $careServiceId, $city)
            ->where('care_status', CareOrderStatus::COMPLETED->value);

        $results = (clone $baseQuery)
            ->selectRaw('DATE(scheduled_start_at) as date')
            ->selectRaw('COUNT(*) as visits_count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Calculate hours for each day
        return $results->map(function ($row) use ($baseQuery) {
            $dayFrom = Carbon::parse($row->date)->startOfDay();
            $dayTo = Carbon::parse($row->date)->endOfDay();

            $dayQuery = (clone $baseQuery)
                ->whereBetween('scheduled_start_at', [$dayFrom, $dayTo]);

            $totalMinutes = $this->calculateTotalMinutes($dayQuery);
            $totalHours = round($totalMinutes / 60, 2);

            return [
                'date' => $row->date,
                'visits_count' => (int) $row->visits_count,
                'total_hours' => $totalHours,
            ];
        });
    }

    /**
     * Get distribution by care service types.
     */
    public function servicesDistribution(
        Carbon $from,
        Carbon $to,
        ?string $helperLevel = null,
        ?string $city = null
    ): Collection {
        $baseQuery = $this->getBaseVisitsQuery($from, $to, $helperLevel, null, $city)
            ->where('care_status', CareOrderStatus::COMPLETED->value);

        return (clone $baseQuery)
            ->join('care_services', 'care_order_details.care_service_id', '=', 'care_services.id')
            ->select('care_services.id', 'care_services.name')
            ->selectRaw('COUNT(*) as visits_count')
            ->groupBy('care_services.id', 'care_services.name')
            ->orderByDesc('visits_count')
            ->get()
            ->map(function ($row) use ($baseQuery) {
                $serviceQuery = (clone $baseQuery)
                    ->where('care_service_id', $row->id);

                $totalMinutes = $this->calculateTotalMinutes($serviceQuery);
                $totalHours = round($totalMinutes / 60, 2);

                return [
                    'service_id' => $row->id,
                    'service_name' => $row->name,
                    'visits_count' => (int) $row->visits_count,
                    'total_hours' => $totalHours,
                ];
            });
    }

    /**
     * Get helpers load and impact.
     */
    public function helpersLoad(
        Carbon $from,
        Carbon $to,
        ?string $helperLevel = null
    ): Collection {
        $baseQuery = CareOrderDetails::query()
            ->whereHas('order', function ($q) {
                $q->where(function ($sq) {
                    $sq->whereHas('careDetails')
                        ->orWhere('metadata->service_type', 'social_care_visit');
                });
            })
            ->whereBetween('scheduled_start_at', [$from, $to])
            ->where('care_status', CareOrderStatus::COMPLETED->value)
            ->whereNotNull('assigned_helper_id');

        if ($helperLevel) {
            $baseQuery->whereHas('assignedHelper', fn ($q) => $q->where('level', $helperLevel));
        }

        $results = (clone $baseQuery)
            ->select('assigned_helper_id')
            ->selectRaw('COUNT(*) as visits_count')
            ->groupBy('assigned_helper_id')
            ->get();

        return $results->map(function ($row) use ($baseQuery) {
            $helper = SocialHelperProfile::find($row->assigned_helper_id);
            if (! $helper) {
                return null;
            }

            $helperVisitsQuery = (clone $baseQuery)
                ->where('assigned_helper_id', $row->assigned_helper_id);

            $totalMinutes = $this->calculateTotalMinutes($helperVisitsQuery);
            $totalHours = round($totalMinutes / 60, 2);

            // Volunteer hours for Community/Friend levels
            $volunteerHours = 0;
            if (in_array($helper->level, ['COMMUNITY_PARTNER', 'BIKUBE_FRIEND'])) {
                $volunteerHours = $totalHours;
            }

            return [
                'helper_id' => $helper->id,
                'helper_name' => $helper->display_name ?? $helper->user->name ?? '—',
                'level' => $helper->level,
                'visits_count' => (int) $row->visits_count,
                'total_hours' => $totalHours,
                'volunteer_hours' => $volunteerHours,
                'rating_avg' => $helper->rating_avg,
                'rating_count' => $helper->rating_count,
                'is_active' => $helper->is_active,
            ];
        })->filter();
    }

    /**
     * Get clients coverage data.
     */
    public function clientsCoverage(
        Carbon $from,
        Carbon $to,
        ?string $city = null
    ): Collection {
        $baseQuery = CareOrderDetails::query()
            ->whereHas('order', function ($q) {
                $q->where(function ($sq) {
                    $sq->whereHas('careDetails')
                        ->orWhere('metadata->service_type', 'social_care_visit');
                });
            })
            ->whereBetween('scheduled_start_at', [$from, $to])
            ->where('care_status', CareOrderStatus::COMPLETED->value)
            ->whereNotNull('client_profile_id');

        if ($city) {
            $baseQuery->whereHas('clientProfile', fn ($q) => $q->where('city', $city));
        }

        $results = (clone $baseQuery)
            ->select('client_profile_id')
            ->selectRaw('COUNT(*) as visits_count')
            ->groupBy('client_profile_id')
            ->get();

        return $results->map(function ($row) use ($baseQuery) {
            $client = ClientProfile::find($row->client_profile_id);
            if (! $client) {
                return null;
            }

            $clientVisitsQuery = (clone $baseQuery)
                ->where('client_profile_id', $row->client_profile_id);

            $totalMinutes = $this->calculateTotalMinutes($clientVisitsQuery);
            $totalHours = round($totalMinutes / 60, 2);

            $hasActivePlan = CarePlan::query()
                ->where('client_profile_id', $client->id)
                ->where('status', 'ACTIVE')
                ->exists();

            $hasTrustedContact = $client->trustedContacts()->exists();

            return [
                'client_id' => $client->id,
                'client_name' => $client->full_name,
                'city' => $client->city,
                'visits_count' => (int) $row->visits_count,
                'total_hours' => $totalHours,
                'has_active_care_plan' => $hasActivePlan,
                'has_trusted_contact' => $hasTrustedContact,
            ];
        })->filter();
    }

    /**
     * Get base query for visits with filters.
     */
    protected function getBaseVisitsQuery(
        Carbon $from,
        Carbon $to,
        ?string $helperLevel = null,
        ?int $careServiceId = null,
        ?string $city = null
    ) {
        $query = CareOrderDetails::query()
            ->whereHas('order', function ($q) {
                $q->where(function ($sq) {
                    $sq->whereHas('careDetails')
                        ->orWhere('metadata->service_type', 'social_care_visit');
                });
            })
            ->whereBetween('scheduled_start_at', [$from, $to]);

        if ($helperLevel) {
            $query->whereHas('assignedHelper', fn ($q) => $q->where('level', $helperLevel));
        }

        if ($careServiceId) {
            $query->where('care_service_id', $careServiceId);
        }

        if ($city) {
            $query->whereHas('clientProfile', fn ($q) => $q->where('city', $city));
        }

        return $query;
    }

    /**
     * Calculate total hours from visits.
     */
    protected function calculateTotalHours($query): float
    {
        $totalMinutes = $this->calculateTotalMinutes($query);

        return round($totalMinutes / 60, 2);
    }

    /**
     * Calculate total minutes from visits (using VisitReport or estimated duration).
     */
    protected function calculateTotalMinutes($query): float
    {
        $visits = (clone $query)
            ->with(['visitReports', 'careService'])
            ->get();

        $totalMinutes = 0;

        foreach ($visits as $visit) {
            $latestReport = $visit->visitReports->sortByDesc('created_at')->first();

            if ($latestReport && $latestReport->started_at && $latestReport->ended_at) {
                // Use actual duration from report
                $minutes = $latestReport->started_at->diffInMinutes($latestReport->ended_at);
                $totalMinutes += $minutes;
            } elseif ($visit->scheduled_start_at && $visit->scheduled_end_at) {
                // Use scheduled duration
                $minutes = $visit->scheduled_start_at->diffInMinutes($visit->scheduled_end_at);
                $totalMinutes += $minutes;
            } elseif ($visit->careService && $visit->careService->base_duration_minutes) {
                // Use service default duration
                $totalMinutes += $visit->careService->base_duration_minutes;
            } else {
                // Default fallback: 60 minutes
                $totalMinutes += 60;
            }
        }

        return $totalMinutes;
    }

    /**
     * Calculate volunteer hours (Community/Friend levels only).
     */
    protected function calculateVolunteerHours(
        Carbon $from,
        Carbon $to,
        ?string $helperLevel = null,
        ?int $careServiceId = null,
        ?string $city = null
    ): float {
        $baseQuery = $this->getBaseVisitsQuery($from, $to, $helperLevel, $careServiceId, $city)
            ->where('care_status', CareOrderStatus::COMPLETED->value)
            ->whereHas('assignedHelper', function ($q) {
                $q->whereIn('level', ['COMMUNITY_PARTNER', 'BIKUBE_FRIEND']);
            });

        return $this->calculateTotalHours($baseQuery);
    }
}
