<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->uuid('tracking_token')->nullable()->unique()->after('metadata');
        });

        DB::table('delivery_orders')
            ->whereNull('tracking_token')
            ->orderBy('id')
            ->chunkById(500, function ($orders) {
                foreach ($orders as $order) {
                    DB::table('delivery_orders')
                        ->where('id', $order->id)
                        ->update(['tracking_token' => (string) Str::uuid()]);
                }
            });

        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->uuid('tracking_token')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->dropUnique(['tracking_token']);
            $table->dropColumn('tracking_token');
        });
    }
};
