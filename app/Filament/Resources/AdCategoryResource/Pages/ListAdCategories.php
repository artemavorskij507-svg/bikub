<?php

namespace App\Filament\Resources\AdCategoryResource\Pages;

use App\Filament\Resources\AdCategoryResource;
use App\Modules\Classifieds\Models\AdCategory;
use App\Modules\Classifieds\Models\AdFeature;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ListAdCategories extends ListRecords
{
    protected static string $resource = AdCategoryResource::class;

    public function mount(): void
    {
        parent::mount();

        $this->ensureLocalAdCategorySchema();
        $this->seedLocalDemoCategoriesIfEmpty();
    }

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function ensureLocalAdCategorySchema(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        if (! Schema::hasTable('ad_categories')) {
            Schema::create('ad_categories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->string('name', 100);
                $table->string('slug')->unique();
                $table->string('meta_title')->nullable();
                $table->text('meta_description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('ad_features')) {
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

        if (! Schema::hasTable('category_feature')) {
            Schema::create('category_feature', function (Blueprint $table) {
                $table->unsignedBigInteger('category_id');
                $table->unsignedBigInteger('feature_id');
                $table->primary(['category_id', 'feature_id']);
            });
        }
    }

    protected function seedLocalDemoCategoriesIfEmpty(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        if (! Schema::hasTable('ad_categories') || ! Schema::hasTable('ad_features')) {
            return;
        }

        if (AdCategory::query()->exists()) {
            return;
        }

        $categories = [
            ['name' => 'Vehicles', 'slug' => 'vehicles', 'is_active' => true],
            ['name' => 'Electronics', 'slug' => 'electronics', 'is_active' => true],
            ['name' => 'Real Estate', 'slug' => 'real-estate', 'is_active' => true],
            ['name' => 'Services', 'slug' => 'services', 'is_active' => true],
        ];

        foreach ($categories as $data) {
            AdCategory::query()->create($data);
        }

        $features = [
            ['name' => 'Price', 'code' => 'price', 'field_type' => 'number', 'is_required' => true],
            ['name' => 'Condition', 'code' => 'condition', 'field_type' => 'select', 'options' => [['label' => 'New', 'value' => 'new'], ['label' => 'Used', 'value' => 'used']], 'is_required' => false],
            ['name' => 'Location', 'code' => 'location', 'field_type' => 'text', 'is_required' => true],
        ];

        foreach ($features as $feature) {
            AdFeature::query()->create($feature);
        }

        if (Schema::hasTable('category_feature')) {
            $categoryIds = AdCategory::query()->pluck('id');
            $featureIds = AdFeature::query()->pluck('id');

            foreach ($categoryIds as $categoryId) {
                foreach ($featureIds as $featureId) {
                    \DB::table('category_feature')->updateOrInsert([
                        'category_id' => $categoryId,
                        'feature_id' => $featureId,
                    ], []);
                }
            }
        }
    }
}