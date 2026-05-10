<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('routing_eta_snapshots')) {
            return;
        }

        Schema::create('routing_eta_snapshots', function (Blueprint $table): void {
            $table->id();

            $table->string('organization_id', 64)->index();
            $table->string('tenant_id', 64)->nullable()->index();

            $table->unsignedBigInteger('service_job_id')->nullable()->index();
            $table->unsignedBigInteger('executor_id')->nullable()->index();
            $table->unsignedBigInteger('dispatch_run_id')->nullable()->index();
            $table->unsignedBigInteger('dispatch_candidate_id')->nullable()->index();

            $table->string('heuristic_provider', 50)->default('internal');
            $table->unsignedInteger('heuristic_eta_seconds')->nullable();
            $table->unsignedInteger('heuristic_distance_meters')->nullable();

            $table->string('routing_provider', 50)->nullable();
            $table->unsignedInteger('routing_eta_seconds')->nullable();
            $table->unsignedInteger('routing_distance_meters')->nullable();

            $table->integer('eta_delta_seconds')->nullable();
            $table->integer('distance_delta_meters')->nullable();

            $table->boolean('would_change_ranking')->default(false)->index();
            $table->json('context')->nullable();

            $table->timestamps();

            $table->index(['service_job_id', 'executor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routing_eta_snapshots');
    }
};

