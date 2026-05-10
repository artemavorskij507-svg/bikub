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
            if (! Schema::hasColumn('assignments', 'score')) {
                $table->decimal('score', 10, 4)->default(0)->after('assignment_mode');
            }
            if (! Schema::hasColumn('assignments', 'score_breakdown')) {
                $table->json('score_breakdown')->nullable()->after('score');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('assignments')) {
            return;
        }

        Schema::table('assignments', function (Blueprint $table): void {
            if (Schema::hasColumn('assignments', 'score_breakdown')) {
                $table->dropColumn('score_breakdown');
            }
            if (Schema::hasColumn('assignments', 'score')) {
                $table->dropColumn('score');
            }
        });
    }
};
