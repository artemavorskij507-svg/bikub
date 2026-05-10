<?php

namespace App\Filament\Resources\DisposalItemResource\Pages;

use App\Filament\Resources\DisposalItemResource;
use App\Models\DisposalItem;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ListDisposalItems extends ListRecords
{
    protected static string $resource = DisposalItemResource::class;

    public function mount(): void
    {
        $this->ensureDisposalItemsSchema();
        parent::mount();
        $this->seedLocalDisposalItemsIfEmpty();
    }

    protected function ensureDisposalItemsSchema(): void
    {
        if (Schema::hasTable('disposal_items')) {
            return;
        }

        try {
            Artisan::call('migrate', [
                '--path' => 'database/migrations/2025_11_16_120000_create_disposal_items_table.php',
                '--force' => true,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Failed to auto-migrate disposal_items table', [
                'error' => $exception->getMessage(),
            ]);
        }
    }

    protected function seedLocalDisposalItemsIfEmpty(): void
    {
        if (! app()->environment('local') || ! Schema::hasTable('disposal_items')) {
            return;
        }

        if (DisposalItem::query()->exists()) {
            return;
        }

        try {
            DisposalItem::query()->create([
                'name' => 'Sofa corner',
                'category' => 'furniture',
                'volume_m3' => 2.400,
                'weight_kg' => 68.500,
                'requires_disassembly' => true,
                'difficulty_coefficient' => 1.20,
                'disposal_path' => 'RECYCLABLE',
                'eco_score' => 72,
                'base_price_nok' => 890.00,
                'is_active' => true,
            ]);

            DisposalItem::query()->create([
                'name' => 'Fridge 200L',
                'category' => 'large_appliance',
                'volume_m3' => 1.100,
                'weight_kg' => 72.000,
                'requires_disassembly' => false,
                'difficulty_coefficient' => 1.00,
                'disposal_path' => 'HAZARDOUS',
                'eco_score' => 55,
                'base_price_nok' => 1150.00,
                'is_active' => true,
            ]);

            DisposalItem::query()->create([
                'name' => 'TV 55 inch',
                'category' => 'electronics',
                'volume_m3' => 0.300,
                'weight_kg' => 16.400,
                'requires_disassembly' => false,
                'difficulty_coefficient' => 0.90,
                'disposal_path' => 'RECYCLABLE',
                'eco_score' => 84,
                'base_price_nok' => 390.00,
                'is_active' => true,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Failed to seed local disposal items', [
                'error' => $exception->getMessage(),
            ]);

            Notification::make()
                ->title('Disposal items seed failed')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }
}
