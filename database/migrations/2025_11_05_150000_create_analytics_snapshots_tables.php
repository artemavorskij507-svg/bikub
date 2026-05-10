<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_metrics', function (Blueprint $t) {
            $t->date('date');
            $t->uuid('zone_id')->nullable();
            $t->string('category', 64)->nullable();

            $t->unsignedInteger('orders_cnt')->default(0);
            $t->bigInteger('orders_revenue_cents')->default(0);
            $t->bigInteger('aov_cents')->default(0);
            $t->unsignedInteger('tasks_completed_cnt')->default(0);
            $t->unsignedInteger('cancellations_cnt')->default(0);
            $t->unsignedInteger('sla_breaches_cnt')->default(0);
            $t->unsignedInteger('avg_eta_sec')->default(0);
            $t->unsignedTinyInteger('courier_utilization_pct')->default(0);

            $t->primary(['date', 'zone_id', 'category']);
        });

        Schema::create('slot_stats_daily', function (Blueprint $t) {
            $t->date('date');
            $t->uuid('zone_id')->nullable();
            $t->string('slot_kind', 32);
            $t->unsignedTinyInteger('hour');

            $t->unsignedSmallInteger('capacity')->default(0);
            $t->unsignedSmallInteger('booked')->default(0);
            $t->unsignedSmallInteger('utilized')->default(0);
            $t->unsignedTinyInteger('on_time_rate_pct')->default(0);
            $t->unsignedInteger('late_cnt')->default(0);

            $t->primary(['date', 'zone_id', 'slot_kind', 'hour']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slot_stats_daily');
        Schema::dropIfExists('daily_metrics');
    }
};
