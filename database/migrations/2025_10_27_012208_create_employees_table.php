<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('partner_id')->nullable()->constrained()->onDelete('set null');
            $table->string('employee_number')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('position')->nullable(); // Ассистент L1, L2, L3, Техник, Диспетчер
            $table->string('status')->default('active'); // active, inactive, on_leave
            $table->boolean('is_verified')->default(false);
            $table->boolean('background_check')->default(false);
            $table->date('hire_date')->nullable();
            $table->json('skills')->nullable(); // Массив навыков
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
