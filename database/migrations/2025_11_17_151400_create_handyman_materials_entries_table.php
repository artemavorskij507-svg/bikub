<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('handyman_materials_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('repair_project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('executor_profile_id')->constrained('executor_profiles')->cascadeOnDelete();
            $table->string('description');
            $table->decimal('quantity', 10, 2)->nullable();
            $table->string('unit')->nullable();
            $table->bigInteger('unit_price_minor')->nullable();
            $table->bigInteger('total_price_minor')->nullable();
            $table->timestamp('purchased_at')->nullable();
            $table->string('receipt_url')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('repair_project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('handyman_materials_entries');
    }
};
