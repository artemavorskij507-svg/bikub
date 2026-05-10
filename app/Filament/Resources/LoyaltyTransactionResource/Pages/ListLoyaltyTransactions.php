<?php

namespace App\Filament\Resources\LoyaltyTransactionResource\Pages;

use App\Filament\Resources\LoyaltyTransactionResource;
use App\Models\LoyaltyTransaction;
use App\Models\User;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ListLoyaltyTransactions extends ListRecords
{
    protected static string $resource = LoyaltyTransactionResource::class;

    public function mount(): void
    {
        parent::mount();

        $this->ensureLocalLoyaltyTransactionsSchema();
        $this->seedLocalLoyaltyTransactionsIfEmpty();
    }

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function ensureLocalLoyaltyTransactionsSchema(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        if (Schema::hasTable('loyalty_transactions')) {
            return;
        }

        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('type', 50);
            $table->integer('points_amount')->default(0);
            $table->text('description')->nullable();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->timestamps();
        });
    }

    protected function seedLocalLoyaltyTransactionsIfEmpty(): void
    {
        if (! app()->environment('local') || ! Schema::hasTable('loyalty_transactions')) {
            return;
        }

        if (LoyaltyTransaction::query()->exists()) {
            return;
        }

        $users = User::query()->limit(2)->get();

        if ($users->isEmpty()) {
            return;
        }

        $u1 = $users->first();
        $u2 = $users->get(1) ?? $u1;

        LoyaltyTransaction::query()->create([
            'user_id' => $u1->id,
            'type' => 'earn',
            'points_amount' => 250,
            'description' => 'Order completion bonus',
            'source_type' => null,
            'source_id' => null,
        ]);

        LoyaltyTransaction::query()->create([
            'user_id' => $u1->id,
            'type' => 'redeem',
            'points_amount' => -100,
            'description' => 'Discount applied to checkout',
            'source_type' => null,
            'source_id' => null,
        ]);

        LoyaltyTransaction::query()->create([
            'user_id' => $u2->id,
            'type' => 'admin_adjustment',
            'points_amount' => 300,
            'description' => 'Manual points adjustment for support case',
            'source_type' => null,
            'source_id' => null,
        ]);
    }
}