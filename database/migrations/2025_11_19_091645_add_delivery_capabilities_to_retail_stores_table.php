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
        Schema::table('retail_stores', function (Blueprint $table) {
            if (! Schema::hasColumn('retail_stores', 'supports_grocery_delivery')) {
                $table->boolean('supports_grocery_delivery')->default(true)->after('has_home_delivery');
            }

            if (! Schema::hasColumn('retail_stores', 'supports_bulky_delivery')) {
                $table->boolean('supports_bulky_delivery')->default(false)->after('supports_grocery_delivery');
            }

            if (! Schema::hasColumn('retail_stores', 'delivery_metadata')) {
                $table->json('delivery_metadata')->nullable()->after('metadata');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('retail_stores', function (Blueprint $table) {
            if (Schema::hasColumn('retail_stores', 'supports_grocery_delivery')) {
                $table->dropColumn('supports_grocery_delivery');
            }

            if (Schema::hasColumn('retail_stores', 'supports_bulky_delivery')) {
                $table->dropColumn('supports_bulky_delivery');
            }

            if (Schema::hasColumn('retail_stores', 'delivery_metadata')) {
                $table->dropColumn('delivery_metadata');
            }
        });
    }
};
