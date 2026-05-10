<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('replan_recommendations')) {
            return;
        }

        Schema::create('replan_recommendations', function (Blueprint $table): void {
            $table->id();

            $table->string('organization_id', 64)->index();
            $table->string('tenant_id', 64)->nullable()->index();

            $table->unsignedBigInteger('service_job_id')->index();
            $table->unsignedBigInteger('current_executor_id')->nullable()->index();
            $table->unsignedBigInteger('recommended_executor_id')->nullable()->index();

            $table->string('type', 50)->index();
            $table->string('severity', 20)->default('medium')->index();
            $table->string('status', 20)->default('open')->index();

            $table->json('payload')->nullable();

            $table->timestamp('detected_at')->index();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamp('applied_at')->nullable();

            $table->timestamps();

            $table->index(['service_job_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('replan_recommendations');
    }
};

