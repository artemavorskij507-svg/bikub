<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_id')->constrained('classified_ads')->onDelete('cascade');
            $table->string('service_type');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            // Assuming 'orders' table exists as per BiKuBe specs
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_payments');
    }
};
