<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('worker_applications')) {
            return;
        }
        Schema::create('worker_applications', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('city')->nullable();
            $table->string('role_requested')->nullable();
            $table->boolean('has_car')->default(false);
            $table->string('vehicle_type')->nullable();
            $table->string('license_info')->nullable();
            $table->json('languages')->nullable();
            $table->text('experience')->nullable();
            $table->text('availability')->nullable();
            $table->json('work_zones')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('new_application');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worker_applications');
    }
};

