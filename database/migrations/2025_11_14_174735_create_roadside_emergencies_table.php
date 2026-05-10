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
        Schema::create('roadside_emergencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('road_helper_id')->nullable()->constrained('road_helper_profiles')->nullOnDelete();
            $table->foreignId('resolved_by_partner_id')->nullable()->constrained('partners')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->enum('incident_type', [
                'jump_start',
                'fuel',
                'flat_tire',
                'accident',
                'tow_needed',
                'engine_no_start',
                'locked_keys',
            ])->nullable();
            $table->text('incident_description')->nullable();
            $table->json('photos')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->enum('status', [
                'new',
                'assigned',
                'on_route',
                'in_progress',
                'completed',
                'failed',
                'cancelled',
            ])->default('new');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['customer_id']);
            $table->index(['road_helper_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roadside_emergencies');
    }
};
