<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eco_certificates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete()->unique();
            $table->string('certificate_uid')->unique();
            $table->string('customer_name');
            $table->json('summary_data')->nullable();
            $table->decimal('co2_saved_kg', 10, 3)->nullable();
            $table->integer('items_reused_count')->nullable();
            $table->timestamp('issued_at');
            $table->string('pdf_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eco_certificates');
    }
};
