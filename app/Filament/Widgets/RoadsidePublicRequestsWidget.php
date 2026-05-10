<?php

namespace App\Filament\Widgets;

use App\Models\RoadsideEmergency;
use Filament\Widgets\Widget;

class RoadsidePublicRequestsWidget extends Widget
{
    protected static string $view = 'filament.widgets.roadside-public-requests-widget';

    protected int|string|array $columnSpan = 'full';

    public function getViewData(): array
    {
        $newPublicRequests = RoadsideEmergency::where('status', 'new')
            ->whereJsonContains('metadata->source', 'public_form')
            ->with(['customer', 'order'])
            ->latest()
            ->limit(5)
            ->get();

        return [
            'newPublicRequests' => $newPublicRequests,
            'totalNewPublic' => RoadsideEmergency::where('status', 'new')
                ->whereJsonContains('metadata->source', 'public_form')
                ->count(),
        ];
    }
}
