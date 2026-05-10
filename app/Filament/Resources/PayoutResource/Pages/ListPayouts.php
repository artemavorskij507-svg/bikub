<?php

namespace App\Filament\Resources\PayoutResource\Pages;

use App\Filament\Resources\PayoutResource;
use App\Models\Payout;
use App\Models\User;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPayouts extends ListRecords
{
    protected static string $resource = PayoutResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->seedLocalDemoPayoutsIfEmpty();
    }

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Создать выплату'),
        ];
    }

    protected function seedLocalDemoPayoutsIfEmpty(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        if (Payout::query()->exists()) {
            return;
        }

        $users = User::query()->orderBy('id')->limit(5)->get();

        if ($users->isEmpty()) {
            return;
        }

        $statusCycle = ['pending', 'processing', 'completed'];
        $methodCycle = ['vipps', 'bank', 'cash'];

        foreach ($users as $index => $user) {
            Payout::query()->create([
                'user_id' => $user->id,
                'amount' => 500 + ($index * 125),
                'currency' => 'NOK',
                'status' => $statusCycle[$index % count($statusCycle)],
                'method' => $methodCycle[$index % count($methodCycle)],
                'note' => 'Auto-generated local demo payout.',
                'admin_note' => 'Created automatically for local admin preview.',
                'processed_at' => now()->subDays($index),
                'processed_by' => auth()->id(),
                'metadata' => [
                    'source' => 'local_demo_seed',
                ],
            ]);
        }
    }
}
