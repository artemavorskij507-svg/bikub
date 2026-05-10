<?php

namespace App\Filament\Pages;

use App\Services\Orders\OrderScenarioRegistry;
use Filament\Pages\Page;

class OrderScenarios extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Catalog / Каталог';

    protected static ?string $navigationLabel = 'Order Scenarios';

    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.order-scenarios';

    public array $scenarios = [];

    public function mount(OrderScenarioRegistry $registry): void
    {
        $all = $registry->all();
        $this->scenarios = array_map(fn (array $s) => $registry->adminMetadata($s), $all);
    }
}
