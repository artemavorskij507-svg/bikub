<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('service_types', function (Blueprint $table) {
            $table->string('code')->nullable()->after('id');
            $table->unsignedBigInteger('service_category_id')->nullable()->after('code');
            $table->json('default_pricing')->nullable()->after('description');
            $table->json('skills')->nullable()->after('default_pricing');
            $table->json('inventory')->nullable()->after('skills');

            $table->foreign('service_category_id')->references('id')->on('service_categories')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_types', function (Blueprint $table) {
            $table->dropForeign(['service_category_id']);
            $table->dropColumn(['code', 'service_category_id', 'default_pricing', 'skills', 'inventory']);
        });
    }
};
