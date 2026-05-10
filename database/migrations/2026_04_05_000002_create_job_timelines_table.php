<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('job_timelines')) {
            Schema::create('job_timelines', function (Blueprint $table) {
                $table->id();
                $table->uuid('organization_id')->nullable()->index();
                $table->foreignId('service_job_id')->constrained('service_jobs')->cascadeOnDelete();
                $table->foreignId('assignment_id')->nullable()->constrained('assignments')->nullOnDelete();
                $table->string('actor_type', 32)->default('system');
                $table->unsignedBigInteger('actor_id')->nullable()->index();
                $table->string('event_type', 64)->index();
                $table->json('event_payload')->nullable();
                $table->timestamp('occurred_at')->index();
                $table->timestamps();

                $table->index(['service_job_id', 'occurred_at'], 'job_timelines_job_occurred_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('job_timelines');
    }
};

