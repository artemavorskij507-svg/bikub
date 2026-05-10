<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('feature_flags')) {
            Schema::create('feature_flags', function (Blueprint $t) {
                $t->uuid('id')->primary();
                $t->string('key')->unique();
                $t->string('name');
                $t->text('description')->nullable();
                $t->boolean('is_active')->default(true);
                $t->boolean('default_on')->default(false);
                $t->unsignedSmallInteger('rollout_percent')->default(100);
                $t->timestampTz('starts_at')->nullable();
                $t->timestampTz('ends_at')->nullable();
                $t->uuid('owner_user_id')->nullable()->index();
                $t->json('rules')->nullable();
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('feature_flag_scopes')) {
            Schema::create('feature_flag_scopes', function (Blueprint $t) {
                $t->uuid('id')->primary();
                $t->uuid('flag_id')->index();
                $t->enum('scope', ['global', 'org', 'zone', 'service_type', 'role', 'user']);
                $t->uuid('ref_id')->nullable();
                $t->string('ref_str')->nullable();
                $t->boolean('enabled')->default(true);
                $t->timestamps();
                $t->unique(['flag_id', 'scope', 'ref_id', 'ref_str']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_flag_scopes');
        Schema::dropIfExists('feature_flags');
    }
};
