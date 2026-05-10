<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('care_services', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('required_level'); // SOCIAL_HELPER, COMMUNITY_PARTNER, BIKUBE_FRIEND
            $table->integer('base_duration_minutes');
            $table->decimal('base_price_nok', 10, 2)->nullable();
            $table->boolean('is_recurring_available')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('code');
            $table->index('required_level');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_services');
    }
};
