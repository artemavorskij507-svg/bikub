<?php

namespace App\Filament\Resources\AdminIpRuleResource\Pages;

use App\Filament\Resources\AdminIpRuleResource;
use App\Models\AdminIpRule;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ListAdminIpRules extends ListRecords
{
    protected static string $resource = AdminIpRuleResource::class;

    public function mount(): void
    {
        parent::mount();

        $this->ensureLocalAdminIpRulesSchema();
        $this->seedLocalDemoAdminIpRulesIfEmpty();
    }

    protected function getTitle(): string
    {
        return 'Admin IP Rules';
    }

    protected function ensureLocalAdminIpRulesSchema(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        if (Schema::hasTable('admin_ip_rules')) {
            return;
        }

        Schema::create('admin_ip_rules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('type')->default('deny');
            $table->string('ip_range');
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    protected function seedLocalDemoAdminIpRulesIfEmpty(): void
    {
        if (! app()->environment('local') || ! Schema::hasTable('admin_ip_rules')) {
            return;
        }

        if (AdminIpRule::query()->exists()) {
            return;
        }

        AdminIpRule::query()->create([
            'type' => 'allow',
            'ip_range' => '127.0.0.1',
            'description' => 'Localhost access for local development',
            'is_active' => true,
        ]);

        AdminIpRule::query()->create([
            'type' => 'deny',
            'ip_range' => '10.255.255.1',
            'description' => 'Demo deny rule for UI testing',
            'is_active' => false,
        ]);
    }
}