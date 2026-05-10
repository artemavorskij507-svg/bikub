<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('schedule_slot_employees')) {
            Schema::create('schedule_slot_employees', function (Blueprint $table) {
                $table->foreignId('slot_id')->constrained('schedule_slots')->cascadeOnDelete();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->json('skills')->nullable();
                $table->boolean('lead')->default(false);
                $table->primary(['slot_id', 'employee_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_slot_employees');
    }
};
