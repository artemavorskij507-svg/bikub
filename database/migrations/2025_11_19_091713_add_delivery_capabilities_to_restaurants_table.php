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
        Schema::table('restaurants', function (Blueprint $table) {
            if (! Schema::hasColumn('restaurants', 'supports_food_delivery')) {
                $table->boolean('supports_food_delivery')->default(true)->after('has_home_delivery');
            }

            if (! Schema::hasColumn('restaurants', 'delivery_metadata')) {
                $table->json('delivery_metadata')->nullable()->after('metadata');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            if (Schema::hasColumn('restaurants', 'supports_food_delivery')) {
                $table->dropColumn('supports_food_delivery');
            }

            if (Schema::hasColumn('restaurants', 'delivery_metadata')) {
                $table->dropColumn('delivery_metadata');
            }
        });
    }
};
