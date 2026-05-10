<?php

namespace App\Filament\Resources\ShopResource\Pages;

use App\Filament\Resources\ShopResource;
use App\Models\User;
use App\Modules\Classifieds\Models\Shop;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ListShops extends ListRecords
{
    protected static string $resource = ShopResource::class;

    public function mount(): void
    {
        parent::mount();

        $this->ensureLocalShopsSchema();
        $this->seedLocalDemoShopsIfEmpty();
    }

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function ensureLocalShopsSchema(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        if (! Schema::hasTable('users') || Schema::hasTable('shops')) {
            return;
        }

        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('cover_path')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->string('address')->nullable();
            $table->json('working_hours')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    protected function seedLocalDemoShopsIfEmpty(): void
    {
        if (! app()->environment('local') || ! Schema::hasTable('shops')) {
            return;
        }

        if (Shop::query()->exists()) {
            return;
        }

        $owner = User::query()->first();

        if (! $owner) {
            return;
        }

        Shop::query()->create([
            'user_id' => $owner->id,
            'name' => 'Bikube Demo Store',
            'slug' => 'bikube-demo-store',
            'description' => 'Auto-generated local demo shop for admin panel.',
            'phone' => '+47 000 00 001',
            'website' => 'https://example.com',
            'address' => 'Oslo',
            'is_verified' => true,
            'is_active' => true,
        ]);

        Shop::query()->create([
            'user_id' => $owner->id,
            'name' => 'Nordic Parts Hub',
            'slug' => 'nordic-parts-hub',
            'description' => 'Auto-generated local demo shop for testing filters and UI.',
            'phone' => '+47 000 00 002',
            'website' => 'https://example.org',
            'address' => 'Bergen',
            'is_verified' => false,
            'is_active' => true,
        ]);
    }
}