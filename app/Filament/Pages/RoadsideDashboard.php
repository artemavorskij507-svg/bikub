<?php

namespace App\Filament\Pages;

use App\Models\Order;
use App\Models\Partner;
use App\Models\RoadHelperProfile;
use App\Models\RoadsideEmergency;
use Filament\Pages\Page;

class RoadsideDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-truck';

    // fix: make Roadside resources visible in GLF Bike Care panel navigation
    protected static ?string $navigationLabel = 'Roadside & Tow';

    protected static ?string $navigationGroup = 'Roadside & Tow';

    protected static ?int $navigationSort = 601;

    // Важно: view указываем с путём для admin-панели
    protected static string $view = 'filament.pages.roadside-dashboard';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        // Ограничиваем доступ только нужными ролями
        if (method_exists($user, 'hasAnyRole')) {
            return $user->hasAnyRole(['admin', 'operator', 'dispatcher']);
        }

        return true;
    }

    public function getHeading(): string
    {
        return 'Roadside & Tow — модуль эвакуатора и помощи на дороге';
    }

    public function getSubheading(): ?string
    {
        return 'Аналитика и управление в реальном времени';
    }

    // fix: force register in navigation for admin panel
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    /**
     * Получить статистику для dashboard
     */
    public function getStats(): array
    {
        $today = now()->startOfDay();
        $thisWeek = now()->startOfWeek();
        $thisMonth = now()->startOfMonth();

        // Roadside Orders
        $roadsideOrders = Order::whereHas('roadsideDetails')
            ->orWhereHas('roadsideEmergency')
            ->orWhereHas('vehicleInspection');

        // Active Emergencies
        $activeEmergencies = RoadsideEmergency::where('status', '!=', 'resolved')
            ->where('status', '!=', 'cancelled')
            ->count();

        // Today's Requests
        $todayRequests = RoadsideEmergency::whereDate('created_at', $today)->count();

        // Active Partners
        $activePartners = Partner::roadside()
            ->where(function ($q) {
                $q->where('is_active', true)->orWhere('active', true);
            })
            ->where(function ($q) {
                $q->where('is_available', true)->orWhereNull('is_available');
            })
            ->count();

        // Active Helpers
        $activeHelpers = RoadHelperProfile::whereHas('user', function ($q) {
            $q->where('is_active', true);
        })->count();

        // Average Response Time (last 30 days)
        // Use metadata->completed_at or order->completed_at for resolved time
        // PostgreSQL compatible: EXTRACT(EPOCH FROM (completed_time - created_at)) / 60 gives minutes
        $avgResponseTime = RoadsideEmergency::where('status', 'completed')
            ->where('created_at', '>=', now()->subDays(30))
            ->with('order')
            ->get()
            ->map(function ($emergency) {
                // Try metadata first, then order completed_at, then use updated_at as fallback
                $completedAt = null;
                if (isset($emergency->metadata['completed_at'])) {
                    try {
                        $completedAt = is_string($emergency->metadata['completed_at'])
                            ? \Carbon\Carbon::parse($emergency->metadata['completed_at'])
                            : ($emergency->metadata['completed_at'] instanceof \Carbon\Carbon
                                ? $emergency->metadata['completed_at']
                                : null);
                    } catch (\Exception $e) {
                        \Log::warning('Failed to parse completed_at from metadata', [
                            'emergency_id' => $emergency->id,
                            'metadata' => $emergency->metadata['completed_at'] ?? null,
                            'error' => $e->getMessage(),
                        ]);
                        $completedAt = null;
                    }
                } elseif ($emergency->order && $emergency->order->completed_at) {
                    $completedAt = $emergency->order->completed_at;
                } elseif ($emergency->updated_at && $emergency->status === 'completed') {
                    // Fallback: use updated_at if status is completed
                    $completedAt = $emergency->updated_at;
                }

                if ($completedAt && $emergency->created_at) {
                    return $emergency->created_at->diffInMinutes($completedAt);
                }

                return null;
            })
            ->filter()
            ->avg() ?? 0;

        // This Week Stats
        $weekStats = [
            'orders' => (clone $roadsideOrders)->where('created_at', '>=', $thisWeek)->count(),
            'completed' => (clone $roadsideOrders)->where('status', 'completed')
                ->where('created_at', '>=', $thisWeek)->count(),
            'revenue' => (clone $roadsideOrders)->where('status', 'completed')
                ->where('created_at', '>=', $thisWeek)
                ->sum('total_amount') ?? 0,
        ];

        // This Month Stats
        $monthStats = [
            'orders' => (clone $roadsideOrders)->where('created_at', '>=', $thisMonth)->count(),
            'completed' => (clone $roadsideOrders)->where('status', 'completed')
                ->where('created_at', '>=', $thisMonth)->count(),
            'revenue' => (clone $roadsideOrders)->where('status', 'completed')
                ->where('created_at', '>=', $thisMonth)
                ->sum('total_amount') ?? 0,
        ];

        return [
            'active_emergencies' => $activeEmergencies,
            'today_requests' => $todayRequests,
            'active_partners' => $activePartners,
            'active_helpers' => $activeHelpers,
            'avg_response_time' => round($avgResponseTime, 1),
            'week_stats' => $weekStats,
            'month_stats' => $monthStats,
        ];
    }
}
