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
        Schema::create('executor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->string('vehicle_type')->nullable()->comment('van, truck, with_lift');
            $table->json('skills')->nullable()->comment('assembly, takelage, electronics, etc.');
            $table->decimal('max_volume', 8, 2)->nullable()->comment('m³');
            $table->decimal('max_weight', 8, 2)->nullable()->comment('kg');
            $table->decimal('insurance_limit', 10, 2)->nullable();
            $table->string('license_number')->nullable();
            $table->date('license_expires_at')->nullable();
            $table->decimal('rating', 3, 2)->default(0)->comment('Average rating 0-5');
            $table->integer('completed_orders_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('is_active');
            $table->index('vehicle_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('executor_profiles');
    }
};
