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
        Schema::table('handyman_order_details', function (Blueprint $table) {
            if (! Schema::hasColumn('handyman_order_details', 'desired_start_at')) {
                $table->timestamp('desired_start_at')->nullable()->after('city');
            }
            if (! Schema::hasColumn('handyman_order_details', 'desired_finish_at')) {
                $table->timestamp('desired_finish_at')->nullable()->after('desired_start_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('handyman_order_details', function (Blueprint $table) {
            if (Schema::hasColumn('handyman_order_details', 'desired_finish_at')) {
                $table->dropColumn('desired_finish_at');
            }
            if (Schema::hasColumn('handyman_order_details', 'desired_start_at')) {
                $table->dropColumn('desired_start_at');
            }
        });
    }
};
