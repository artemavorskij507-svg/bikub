<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('shop_id')->nullable()->constrained('shops')->nullOnDelete();
            $table->string('file_path');
            $table->string('file_type')->default('xml'); // xml, csv
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->integer('processed_count')->default(0);
            $table->integer('error_count')->default(0);
            $table->jsonb('report')->nullable(); // Log of errors
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_imports');
    }
};
