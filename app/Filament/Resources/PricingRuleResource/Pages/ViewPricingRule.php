<?php

namespace App\Filament\Resources\PricingRuleResource\Pages;

use App\Filament\Resources\PricingRuleResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPricingRule extends ViewRecord
{
    protected static string $resource = PricingRuleResource::class;

    protected function getActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
