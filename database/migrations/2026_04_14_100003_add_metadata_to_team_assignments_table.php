<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('team_assignments')) {
            return;
        }

        Schema::table('team_assignments', function (Blueprint $table): void {
            if (! Schema::hasColumn('team_assignments', 'metadata')) {
                $table->json('metadata')->nullable()->after('eta_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('team_assignments')) {
            return;
        }

        Schema::table('team_assignments', function (Blueprint $table): void {
            if (Schema::hasColumn('team_assignments', 'metadata')) {
                $table->dropColumn('metadata');
            }
        });
    }
};

