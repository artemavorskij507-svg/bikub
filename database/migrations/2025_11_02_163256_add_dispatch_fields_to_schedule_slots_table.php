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
        Schema::table('schedule_slots', function (Blueprint $table) {
            // Добавить zone_id если его нет
            if (! Schema::hasColumn('schedule_slots', 'zone_id')) {
                $table->foreignId('zone_id')->nullable()->constrained('geo_zones')->nullOnDelete()->after('code');
            }

            if (! Schema::hasColumn('schedule_slots', 'capacity_limit')) {
                $table->integer('capacity_limit')->default(10)->after('max_orders');
            }
            if (! Schema::hasColumn('schedule_slots', 'reserved_count')) {
                $table->integer('reserved_count')->default(0)->after('capacity_limit');
            }
            if (! Schema::hasColumn('schedule_slots', 'override_reason')) {
                $table->text('override_reason')->nullable()->after('reserved_count');
            }
            if (! Schema::hasColumn('schedule_slots', 'is_closed')) {
                $table->boolean('is_closed')->default(false)->after('override_reason');
            }
            if (! Schema::hasColumn('schedule_slots', 'cutoff_at')) {
                $table->timestamp('cutoff_at')->nullable()->after('is_closed');
            }
            if (! Schema::hasColumn('schedule_slots', 'overbooking_pct')) {
                $table->integer('overbooking_pct')->default(0)->after('cutoff_at');
            }

            // Индекс только если zone_id существует
            if (Schema::hasColumn('schedule_slots', 'zone_id')) {
                $table->index(['zone_id', 'is_closed']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedule_slots', function (Blueprint $table) {
            $table->dropIndex(['zone_id', 'is_closed']);

            $columns = [
                'capacity_limit', 'reserved_count', 'override_reason',
                'is_closed', 'cutoff_at', 'overbooking_pct',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('schedule_slots', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
