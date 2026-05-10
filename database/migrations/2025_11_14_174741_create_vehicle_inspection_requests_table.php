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
        Schema::create('vehicle_inspection_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('preset_id')->nullable()->constrained('vehicle_inspection_presets')->nullOnDelete();
            $table->foreignId('assigned_helper_id')->nullable()->constrained('road_helper_profiles')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('seller_name')->nullable();
            $table->string('seller_phone')->nullable();
            $table->string('vehicle_make')->nullable();
            $table->string('vehicle_model')->nullable();
            $table->string('vehicle_year')->nullable();
            $table->string('vin_code')->nullable();
            $table->string('address')->nullable();
            $table->timestamp('requested_time')->nullable();
            $table->enum('status', [
                'new',
                'assigned',
                'in_progress',
                'finished',
                'cancelled',
            ])->default('new');
            $table->json('report_json')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['customer_id']);
            $table->index(['assigned_helper_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_inspection_requests');
    }
};
