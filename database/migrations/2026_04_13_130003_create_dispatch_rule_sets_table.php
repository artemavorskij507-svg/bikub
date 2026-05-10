<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dispatch_rule_sets')) {
            return;
        }

        Schema::create('dispatch_rule_sets', function (Blueprint $table): void {
            $table->id();
            $table->string('organization_id', 64)->nullable()->index();
            $table->string('tenant_id', 64)->nullable()->index();
            $table->string('service_domain', 64)->index();
            $table->string('job_kind', 64)->nullable()->index();
            $table->string('rule_key', 100)->index();
            $table->json('rule_value_json')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatch_rule_sets');
    }
};
