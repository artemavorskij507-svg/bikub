<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('team_assignments')) {
            return;
        }

        Schema::create('team_assignments', function (Blueprint $table): void {
            $table->id();
            $table->string('organization_id', 64)->nullable()->index();
            $table->string('tenant_id', 64)->nullable()->index();
            $table->unsignedBigInteger('service_job_id')->index();
            $table->unsignedBigInteger('team_lead_executor_id')->nullable()->index();
            $table->json('member_executor_ids_json')->nullable();
            $table->string('status', 32)->default('proposed')->index();
            $table->timestamp('eta_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_assignments');
    }
};
