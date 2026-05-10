<?php

namespace App\Filament\Pages\Security;

use App\Filament\Resources\AdminIpRuleResource;
use Filament\Pages\Page;

class AdminIpRules extends Page
{
    protected static string $view = 'filament.security.admin-ip-rules';

    protected static ?string $title = 'Admin IP Rules';

    public function mount(): void
    {
        $this->redirect(AdminIpRuleResource::getUrl('index'));
    }
}
