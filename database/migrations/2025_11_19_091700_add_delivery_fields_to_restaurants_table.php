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
            if (! Schema::hasColumn('restaurants', 'brand')) {
                $table->string('brand')->nullable()->after('name');
            }

            if (! Schema::hasColumn('restaurants', 'city')) {
                $table->string('city')->nullable()->after('address');
            }

            if (! Schema::hasColumn('restaurants', 'postcode')) {
                $table->string('postcode', 32)->nullable()->after('city');
            }

            if (! Schema::hasColumn('restaurants', 'country')) {
                $table->string('country', 64)->nullable()->default('Norway')->after('postcode');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            if (Schema::hasColumn('restaurants', 'brand')) {
                $table->dropColumn('brand');
            }

            if (Schema::hasColumn('restaurants', 'city')) {
                $table->dropColumn('city');
            }

            if (Schema::hasColumn('restaurants', 'postcode')) {
                $table->dropColumn('postcode');
            }

            if (Schema::hasColumn('restaurants', 'country')) {
                $table->dropColumn('country');
            }
        });
    }
};
