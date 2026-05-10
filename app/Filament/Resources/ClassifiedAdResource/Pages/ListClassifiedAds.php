<?php

namespace App\Filament\Resources\ClassifiedAdResource\Pages;

use App\Filament\Resources\ClassifiedAdResource;
use App\Modules\Classifieds\Models\AdCategory;
use App\Modules\Classifieds\Models\ClassifiedAd;
use App\Models\User;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class ListClassifiedAds extends ListRecords
{
    protected static string $resource = ClassifiedAdResource::class;

    public function mount(): void
    {
        parent::mount();

        $this->ensureLocalClassifiedAdsSchema();
        $this->seedLocalDemoClassifiedAdsIfEmpty();
    }

    protected function ensureLocalClassifiedAdsSchema(): void
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

        if (Schema::hasTable('classified_ads')) {
            return;
        }

        Schema::create('classified_ads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('shop_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->json('price_details')->nullable();
            $table->integer('price_value')->nullable();
            $table->string('status', 20)->default('draft');
            $table->string('address')->nullable();
            $table->boolean('is_premium')->default(false);
            $table->timestamp('premium_expires_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->text('moderation_reason')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('slug')->nullable();
            $table->timestamp('bumped_at')->nullable();
            $table->timestamp('highlight_expires_at')->nullable();
            $table->timestamp('top_expires_at')->nullable();
            $table->timestamp('vip_expires_at')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->unsignedBigInteger('views_count')->default(0);
            $table->timestamps();
        });
    }

    protected function seedLocalDemoClassifiedAdsIfEmpty(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        if (! Schema::hasTable('classified_ads') || ! Schema::hasTable('ad_categories')) {
            return;
        }

        if (ClassifiedAd::query()->exists()) {
            return;
        }

        $userId = Auth::id() ?? User::query()->value('id');
        if ($userId === null) {
            return;
        }

        $category = AdCategory::query()->first();

        if (! $category) {
            $category = AdCategory::query()->create([
                'name' => 'General',
                'slug' => 'general',
                'is_active' => true,
            ]);
        }

        $items = [
            [
                'title' => 'Used Bike in Good Condition',
                'description' => 'City bike, ready to ride.',
                'price_value' => 45900,
                'status' => 'published',
                'address' => 'Oslo',
                'published_at' => now()->subHours(4),
            ],
            [
                'title' => 'Apartment Chair Set',
                'description' => 'Set of 4 wooden chairs.',
                'price_value' => 12900,
                'status' => 'moderation',
                'address' => 'Bergen',
            ],
        ];

        foreach ($items as $item) {
            ClassifiedAd::query()->create(array_merge($item, [
                'user_id' => $userId,
                'shop_id' => null,
                'category_id' => $category->id,
                'is_premium' => false,
            ]));
        }
    }
}
