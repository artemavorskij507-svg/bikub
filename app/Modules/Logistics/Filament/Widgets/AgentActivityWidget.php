<?php

namespace App\Modules\Logistics\Filament\Widgets;

use Filament\Widgets\Widget;

class AgentActivityWidget extends Widget
{
    protected static string $view = 'filament.widgets.logistics-placeholder';

    protected int|string|array $columnSpan = 'full';
}
