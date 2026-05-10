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
        Schema::table('roadside_emergencies', function (Blueprint $table) {
            if (! Schema::hasColumn('roadside_emergencies', 'tracking_token')) {
                $table->string('tracking_token')->unique()->nullable()->after('order_id');
            }
            if (! Schema::hasColumn('roadside_emergencies', 'tracking_url')) {
                $table->string('tracking_url')->nullable()->after('tracking_token');
            }
            if (! Schema::hasColumn('roadside_emergencies', 'customer_notified_at')) {
                $table->timestamp('customer_notified_at')->nullable()->after('tracking_url');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roadside_emergencies', function (Blueprint $table) {
            if (Schema::hasColumn('roadside_emergencies', 'customer_notified_at')) {
                $table->dropColumn('customer_notified_at');
            }
            if (Schema::hasColumn('roadside_emergencies', 'tracking_url')) {
                $table->dropColumn('tracking_url');
            }
            if (Schema::hasColumn('roadside_emergencies', 'tracking_token')) {
                $table->dropUnique(['tracking_token']);
                $table->dropColumn('tracking_token');
            }
        });
    }
};
