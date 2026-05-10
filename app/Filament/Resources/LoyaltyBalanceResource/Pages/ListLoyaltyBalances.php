<?php

namespace App\Filament\Resources\LoyaltyBalanceResource\Pages;

use App\Filament\Resources\LoyaltyBalanceResource;
use App\Models\LoyaltyBalance;
use App\Models\User;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ListLoyaltyBalances extends ListRecords
{
    protected static string $resource = LoyaltyBalanceResource::class;

    public function mount(): void
    {
        parent::mount();

        $this->ensureLocalLoyaltyBalancesSchema();
        $this->seedLocalLoyaltyBalancesIfEmpty();
    }

    protected function getActions(): array
    {
        return [
            Actions\Action::make('manage')
                ->label('Керування балами')
                ->url(LoyaltyBalanceResource::getUrl('manage'))
                ->icon('heroicon-o-cog')
                ->color('info'),
            Actions\CreateAction::make(),
        ];
    }

    protected function ensureLocalLoyaltyBalancesSchema(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        if (Schema::hasTable('loyalty_balances')) {
            return;
        }

        Schema::create('loyalty_balances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->integer('points')->default(0);
            $table->integer('lifetime_points')->default(0);
            $table->timestamps();
        });
    }

    protected function seedLocalLoyaltyBalancesIfEmpty(): void
    {
        if (! app()->environment('local') || ! Schema::hasTable('loyalty_balances')) {
            return;
        }

        if (LoyaltyBalance::query()->exists()) {
            return;
        }

        $users = User::query()->limit(3)->get();

        foreach ($users as $index => $user) {
            LoyaltyBalance::query()->create([
                'user_id' => $user->id,
                'points' => [1200, 450, 0][$index] ?? 100,
                'lifetime_points' => [4300, 1720, 300][$index] ?? 500,
            ]);
        }
    }
}