<?php

namespace App\Filament\Resources\CommunityPointsBalanceResource\Pages;

use App\Filament\Resources\CommunityPointsBalanceResource;
use App\Support\Local\SocialCareLocalDemoSeeder;
use Filament\Resources\Pages\ListRecords;

class ListCommunityPointsBalances extends ListRecords
{
    protected static string $resource = CommunityPointsBalanceResource::class;

    public function mount(): void
    {
        SocialCareLocalDemoSeeder::run();
        parent::mount();
    }
}
