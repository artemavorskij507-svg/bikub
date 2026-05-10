<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'schedule_slot_id')) {
                $table->foreignId('schedule_slot_id')->nullable()->constrained('schedule_slots')->nullOnDelete();
            }
            if (! Schema::hasColumn('orders', 'delivery_start_time')) {
                $table->timestamp('delivery_start_time')->nullable();
            }
            if (! Schema::hasColumn('orders', 'delivery_end_time')) {
                $table->timestamp('delivery_end_time')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'schedule_slot_id')) {
                $table->dropConstrainedForeignId('schedule_slot_id');
            }
            if (Schema::hasColumn('orders', 'delivery_start_time')) {
                $table->dropColumn('delivery_start_time');
            }
            if (Schema::hasColumn('orders', 'delivery_end_time')) {
                $table->dropColumn('delivery_end_time');
            }
        });
    }
};
