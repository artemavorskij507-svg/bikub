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
            if (! Schema::hasColumn('retail_stores', 'brand')) {
                $table->string('brand')->nullable()->after('name');
            }

            if (! Schema::hasColumn('retail_stores', 'city')) {
                $table->string('city')->nullable()->after('address');
            }

            if (! Schema::hasColumn('retail_stores', 'postcode')) {
                $table->string('postcode', 32)->nullable()->after('city');
            }

            if (! Schema::hasColumn('retail_stores', 'country')) {
                $table->string('country', 64)->nullable()->default('Norway')->after('postcode');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('retail_stores', function (Blueprint $table) {
            if (Schema::hasColumn('retail_stores', 'brand')) {
                $table->dropColumn('brand');
            }

            if (Schema::hasColumn('retail_stores', 'city')) {
                $table->dropColumn('city');
            }

            if (Schema::hasColumn('retail_stores', 'postcode')) {
                $table->dropColumn('postcode');
            }

            if (Schema::hasColumn('retail_stores', 'country')) {
                $table->dropColumn('country');
            }
        });
    }
};
