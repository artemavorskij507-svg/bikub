<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_features', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code')->unique();
            $table->enum('field_type', ['text', 'select', 'checkbox', 'number', 'textarea']);
            $table->jsonb('options')->nullable();
            $table->boolean('is_required')->default(false);
            $table->timestamps();
        });

        Schema::create('category_feature', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained('ad_categories')->onDelete('cascade');
            $table->foreignId('feature_id')->constrained('ad_features')->onDelete('cascade');
            $table->primary(['category_id', 'feature_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_feature');
        Schema::dropIfExists('ad_features');
    }
};
