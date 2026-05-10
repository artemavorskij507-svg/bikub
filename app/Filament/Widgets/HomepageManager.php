<?php

namespace App\Filament\Widgets;

use App\Models\ServiceType;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Collection;

class HomepageManager extends Widget
{
    protected static ?string $pollingInterval = null;

    protected static ?int $sort = 1;

    protected static string $view = 'filament.widgets.homepage-manager';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        return [
            'services' => $this->getServices(),
            'stats' => $this->getStats(),
        ];
    }

    public function getServices(): Collection
    {
        try {
            return ServiceType::active()
                ->with('serviceCategory')
                ->orderBy('sort_order')
                ->orderBy('updated_at', 'desc')
                ->take(12)
                ->get();
        } catch (\Exception $e) {
            return new Collection;
        }
    }

    public function getStats(): array
    {
        try {
            return [
                'total_services' => ServiceType::active()->count(),
                'featured_services' => ServiceType::active()
                    ->whereNotNull('sort_order')
                    ->where('sort_order', '>', 0)
                    ->count(),
            ];
        } catch (\Exception $e) {
            return [
                'total_services' => 0,
                'featured_services' => 0,
            ];
        }
    }
}
