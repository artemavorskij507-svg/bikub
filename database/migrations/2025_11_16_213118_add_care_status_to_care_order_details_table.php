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
        if (! Schema::hasTable('care_order_details')) {
            return;
        }

        Schema::table('care_order_details', function (Blueprint $table) {
            $table->string('care_status')->default('SCHEDULED')->after('care_plan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('care_order_details') || ! Schema::hasColumn('care_order_details', 'care_status')) {
            return;
        }

        Schema::table('care_order_details', function (Blueprint $table) {
            $table->dropColumn('care_status');
        });
    }
};
