<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ops_saved_filters')) {
            return;
        }

        Schema::create('ops_saved_filters', function (Blueprint $table): void {
            $table->id();
            $table->uuid('organization_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('name', 120);
            $table->json('filters_json');
            $table->boolean('is_shared')->default(false);
            $table->timestamps();

            $table->unique(['organization_id', 'user_id', 'name'], 'ops_saved_filters_org_user_name_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ops_saved_filters');
    }
};

