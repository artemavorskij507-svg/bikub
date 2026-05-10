<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slot_templates', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->uuid('org_id')->index();
            $t->uuid('zone_id')->nullable()->index();
            $t->string('kind', 16)->default('delivery')->index();
            $t->unsignedSmallInteger('weekday');
            $t->time('start_time');
            $t->time('end_time');
            $t->unsignedSmallInteger('step_min')->default(60);
            $t->unsignedInteger('capacity_total')->default(10);
            $t->string('oversell_policy', 16)->default('deny');
            $t->json('meta')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slot_templates');
    }
};
