<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('handyman_kpi_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('executor_profile_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamp('calculated_at')->nullable();

            $table->unsignedInteger('total_orders')->default(0);
            $table->unsignedInteger('completed_orders')->default(0);
            $table->unsignedInteger('cancelled_orders')->default(0);

            $table->unsignedInteger('claims_count')->default(0);
            $table->unsignedInteger('serious_claims_count')->default(0);

            $table->decimal('avg_rating', 3, 2)->default(0);
            $table->unsignedInteger('ratings_count')->default(0);

            $table->decimal('on_time_rate', 5, 2)->default(0);
            $table->unsignedInteger('avg_duration_minutes')->default(0);

            $table->unsignedInteger('repeat_clients_count')->default(0);
            $table->unsignedInteger('unique_clients_count')->default(0);

            $table->integer('quality_score')->default(0);

            $table->timestamps();

            $table->unique('executor_profile_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('handyman_kpi_snapshots');
    }
};
