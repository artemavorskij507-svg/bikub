<?php

namespace App\Filament\Resources\AdFeatureResource\Pages;

use App\Filament\Resources\AdFeatureResource;
use App\Modules\Classifieds\Models\AdFeature;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ListAdFeatures extends ListRecords
{
    protected static string $resource = AdFeatureResource::class;

    public function mount(): void
    {
        parent::mount();

        $this->ensureLocalAdFeaturesSchema();
        $this->seedLocalDemoFeaturesIfEmpty();
    }

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function ensureLocalAdFeaturesSchema(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        if (Schema::hasTable('ad_features')) {
            return;
        }

        Schema::create('ad_features', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code')->unique();
            $table->string('field_type', 20);
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(false);
            $table->timestamps();
        });
    }

    protected function seedLocalDemoFeaturesIfEmpty(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        if (! Schema::hasTable('ad_features')) {
            return;
        }

        if (AdFeature::query()->exists()) {
            return;
        }

        $features = [
            [
                'name' => 'Price',
                'code' => 'price',
                'field_type' => 'number',
                'is_required' => true,
            ],
            [
                'name' => 'Condition',
                'code' => 'condition',
                'field_type' => 'select',
                'options' => [
                    ['label' => 'New', 'value' => 'new'],
                    ['label' => 'Used', 'value' => 'used'],
                ],
                'is_required' => false,
            ],
            [
                'name' => 'Location',
                'code' => 'location',
                'field_type' => 'text',
                'is_required' => true,
            ],
        ];

        foreach ($features as $feature) {
            AdFeature::query()->create($feature);
        }
    }
}