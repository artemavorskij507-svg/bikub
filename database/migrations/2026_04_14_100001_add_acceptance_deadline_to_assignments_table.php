<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('assignments')) {
            return;
        }

        Schema::table('assignments', function (Blueprint $table): void {
            if (! Schema::hasColumn('assignments', 'acceptance_deadline_at')) {
                $table->timestamp('acceptance_deadline_at')->nullable()->index()->after('eta_at');
            }

            if (! Schema::hasColumn('assignments', 'acceptance_timeout_seconds')) {
                $table->unsignedInteger('acceptance_timeout_seconds')->nullable()->after('acceptance_deadline_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('assignments')) {
            return;
        }

        Schema::table('assignments', function (Blueprint $table): void {
            if (Schema::hasColumn('assignments', 'acceptance_timeout_seconds')) {
                $table->dropColumn('acceptance_timeout_seconds');
            }

            if (Schema::hasColumn('assignments', 'acceptance_deadline_at')) {
                $table->dropColumn('acceptance_deadline_at');
            }
        });
    }
};

