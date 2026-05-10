<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disposal_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->index();
            $table->string('category'); // furniture, large_appliance, etc.
            $table->decimal('volume_m3', 8, 3)->nullable();
            $table->decimal('weight_kg', 8, 3)->nullable();
            $table->boolean('requires_disassembly')->default(false);
            $table->decimal('difficulty_coefficient', 5, 2)->default(1.00);
            $table->string('disposal_path'); // RECYCLABLE, DONATABLE, HAZARDOUS, LANDFILL
            $table->integer('eco_score')->nullable();
            $table->decimal('base_price_nok', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disposal_items');
    }
};
