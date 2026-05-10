<?php

namespace App\Filament\Pages;

use App\Models\Order;
use App\Models\Partner;
use App\Models\RoadHelperProfile;
use App\Models\RoadsideEmergency;
use App\Models\RoadsidePreset;
use App\Models\VehicleInspectionPreset;
use App\Models\VehicleInspectionRequest;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

// TODO fixed by Cursor: internal Roadside debug/demo page, not for production navigation logic
class RoadsideDebugPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog';

    protected static string $view = 'filament.pages.roadside-debug';

    protected static ?string $navigationLabel = 'Debug & Demo';

    protected static ?string $navigationGroup = 'System & Operations';

    protected static ?int $navigationSort = 109;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['admin', 'operator', 'dispatcher']) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function getTitle(): string
    {
        return 'Roadside & Tow Debug & Demo';
    }

    /**
     * Get statistics for the module.
     */
    public function getStats(): array
    {
        $hasOrders = Schema::hasTable('orders');

        return [
            'partners' => Schema::hasTable('partners') ? Partner::roadside()->count() : 0,
            'helpers' => Schema::hasTable('road_helper_profiles') ? RoadHelperProfile::count() : 0,
            'roadside_presets' => Schema::hasTable('roadside_presets') ? RoadsidePreset::count() : 0,
            'inspection_presets' => Schema::hasTable('vehicle_inspection_presets') ? VehicleInspectionPreset::count() : 0,
            'active_emergencies' => Schema::hasTable('roadside_emergencies')
                ? RoadsideEmergency::whereIn('status', ['new', 'assigned', 'in_progress'])->count()
                : 0,
            'total_emergencies' => Schema::hasTable('roadside_emergencies') ? RoadsideEmergency::count() : 0,
            'active_inspections' => Schema::hasTable('vehicle_inspection_requests')
                ? VehicleInspectionRequest::whereIn('status', ['pending', 'assigned', 'in_progress'])->count()
                : 0,
            'total_inspections' => Schema::hasTable('vehicle_inspection_requests') ? VehicleInspectionRequest::count() : 0,
            'active_roadside_orders' => $hasOrders
                ? Order::query()
                    ->whereIn('status', ['pending', 'assigned', 'in_progress'])
                    ->where(function ($query) {
                        if (Schema::hasTable('roadside_assistance_details')) {
                            $query->orWhereHas('roadsideDetails');
                        }

                        if (Schema::hasTable('roadside_emergencies')) {
                            $query->orWhereHas('roadsideEmergency');
                        }

                        if (Schema::hasTable('vehicle_inspection_requests')) {
                            $query->orWhereHas('vehicleInspection');
                        }
                    })
                    ->count()
                : 0,
        ];
    }

    /**
     * Get recent emergencies.
     */
    public function getRecentEmergencies()
    {
        if (! Schema::hasTable('roadside_emergencies')) {
            return collect();
        }

        return RoadsideEmergency::with(['customer', 'helper', 'partner'])
            ->latest()
            ->limit(5)
            ->get();
    }

    /**
     * Get recent inspection requests.
     */
    public function getRecentInspections()
    {
        if (! Schema::hasTable('vehicle_inspection_requests')) {
            return collect();
        }

        return VehicleInspectionRequest::with(['customer', 'preset', 'helper'])
            ->latest()
            ->limit(5)
            ->get();
    }

    /**
     * Seed demo data.
     */
    public function seedDemoData(): void
    {
        try {
            $exitCode = Artisan::call('roadside:seed-demo');

            if ($exitCode === 0) {
                Notification::make()
                    ->title('Äåìî-äàííûå óñïåøíî çàïîëíåíû')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Îøèáêà ïğè çàïîëíåíèè äåìî-äàííûõ')
                    ->body('Êîìàíäà âåğíóëà êîä îøèáêè: '.$exitCode)
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Îøèáêà ïğè çàïîëíåíèè äåìî-äàííûõ')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
