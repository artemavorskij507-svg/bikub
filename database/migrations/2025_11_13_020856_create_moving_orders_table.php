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
        Schema::create('moving_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('pending')->comment('pending, confirmed, in_progress, completed, cancelled');
            $table->json('from_address')->comment('street, building_type, floor, has_elevator, lat, lng');
            $table->json('to_address')->comment('street, building_type, floor, has_elevator, lat, lng');
            $table->json('inventory')->nullable()->comment('items by room');
            $table->json('services')->nullable()->comment('selected services: assembly, disassembly, packaging, etc.');
            $table->string('package_type')->default('standard')->comment('economy, standard, premium');
            $table->timestamp('scheduled_at')->nullable();
            $table->foreignId('executor_team_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->decimal('total_volume', 8, 2)->nullable()->comment('m³');
            $table->decimal('total_weight', 8, 2)->nullable()->comment('kg');
            $table->decimal('estimated_price', 10, 2)->nullable();
            $table->decimal('final_price', 10, 2)->nullable();
            $table->integer('estimated_duration_minutes')->nullable();
            $table->integer('nps_score')->nullable()->comment('Net Promoter Score 0-10');
            $table->text('customer_notes')->nullable();
            $table->text('executor_notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('user_id');
            $table->index('scheduled_at');
            $table->index('executor_team_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('moving_orders');
    }
};
