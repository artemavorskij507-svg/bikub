<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('service_jobs')) {
            return;
        }

        Schema::table('service_jobs', function (Blueprint $table): void {
            if (! Schema::hasColumn('service_jobs', 'required_team_size')) {
                $table->unsignedTinyInteger('required_team_size')->nullable()->after('service_duration_minutes');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('service_jobs')) {
            return;
        }

        Schema::table('service_jobs', function (Blueprint $table): void {
            if (Schema::hasColumn('service_jobs', 'required_team_size')) {
                $table->dropColumn('required_team_size');
            }
        });
    }
};

