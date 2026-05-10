<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedule_slots', function (Blueprint $t) {
            if (! Schema::hasColumn('schedule_slots', 'org_id')) {
                $t->uuid('org_id')->nullable()->index();
            }
            if (! Schema::hasColumn('schedule_slots', 'zone_id')) {
                $t->uuid('zone_id')->nullable()->index();
            }
            if (! Schema::hasColumn('schedule_slots', 'kind')) {
                $t->string('kind', 16)->default('delivery')->index();
            }
            if (! Schema::hasColumn('schedule_slots', 'start_at')) {
                $t->timestampTz('start_at')->nullable()->index();
            }
            if (! Schema::hasColumn('schedule_slots', 'end_at')) {
                $t->timestampTz('end_at')->nullable()->index();
            }
            if (! Schema::hasColumn('schedule_slots', 'status')) {
                $t->string('status', 16)->default('open')->index();
            }
            if (! Schema::hasColumn('schedule_slots', 'capacity_total')) {
                $t->unsignedInteger('capacity_total')->default(10);
            }
            if (! Schema::hasColumn('schedule_slots', 'capacity_soft_limit')) {
                $t->unsignedInteger('capacity_soft_limit')->nullable();
            }
            if (! Schema::hasColumn('schedule_slots', 'reserved_count')) {
                $t->unsignedInteger('reserved_count')->default(0)->index();
            }
            if (! Schema::hasColumn('schedule_slots', 'confirmed_count')) {
                $t->unsignedInteger('confirmed_count')->default(0)->index();
            }
            if (! Schema::hasColumn('schedule_slots', 'oversell_policy')) {
                $t->string('oversell_policy', 16)->default('deny')->index();
            }
            if (! Schema::hasColumn('schedule_slots', 'hold_ttl_sec')) {
                $t->unsignedInteger('hold_ttl_sec')->default(900);
            }
            if (! Schema::hasColumn('schedule_slots', 'label')) {
                $t->string('label')->nullable();
            }
            if (! Schema::hasColumn('schedule_slots', 'color')) {
                $t->string('color')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('schedule_slots', function (Blueprint $t) {
            $drops = ['org_id', 'zone_id', 'kind', 'start_at', 'end_at', 'status', 'capacity_total', 'capacity_soft_limit', 'reserved_count', 'confirmed_count', 'oversell_policy', 'hold_ttl_sec', 'label', 'color'];
            foreach ($drops as $col) {
                if (Schema::hasColumn('schedule_slots', $col)) {
                    $t->dropColumn($col);
                }
            }
        });
    }
};
