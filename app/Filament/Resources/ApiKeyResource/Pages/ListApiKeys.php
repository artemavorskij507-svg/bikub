<?php

namespace App\Filament\Resources\ApiKeyResource\Pages;

use App\Filament\Resources\ApiKeyResource;
use App\Models\ApiKey;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ListApiKeys extends ListRecords
{
    protected static string $resource = ApiKeyResource::class;

    public function mount(): void
    {
        parent::mount();

        $this->ensureLocalApiKeysSchema();
        $this->seedLocalDemoApiKeyIfEmpty();
    }

    protected function getTitle(): string
    {
        return 'API Keys';
    }

    protected function ensureLocalApiKeysSchema(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        if (Schema::hasTable('api_keys')) {
            return;
        }

        Schema::create('api_keys', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('owner_type');
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->string('name');
            $table->string('key_hash');
            $table->json('scopes')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['owner_type', 'owner_id']);
        });
    }

    protected function seedLocalDemoApiKeyIfEmpty(): void
    {
        if (! app()->environment('local') || ! Schema::hasTable('api_keys')) {
            return;
        }

        if (ApiKey::query()->exists()) {
            return;
        }

        ApiKey::query()->create([
            'owner_type' => 'service',
            'owner_id' => null,
            'name' => 'Local Demo Integration Key',
            'key_hash' => hash('sha256', 'local-demo-api-key'),
            'scopes' => ['agency-agents:read', 'agency-agents:write', 'admin:monitoring'],
            'last_used_at' => now()->subMinutes(15),
            'expires_at' => now()->addMonths(6),
            'revoked_at' => null,
        ]);
    }
}