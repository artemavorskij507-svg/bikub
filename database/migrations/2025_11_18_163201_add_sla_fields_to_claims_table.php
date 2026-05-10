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
        Schema::table('claims', function (Blueprint $table) {
            if (! Schema::hasColumn('claims', 'sla_response_due_at')) {
                $table->timestamp('sla_response_due_at')->nullable()->after('status');
            }

            if (! Schema::hasColumn('claims', 'sla_resolution_due_at')) {
                $table->timestamp('sla_resolution_due_at')->nullable()->after('sla_response_due_at');
            }

            if (! Schema::hasColumn('claims', 'responded_at')) {
                $table->timestamp('responded_at')->nullable()->after('sla_resolution_due_at');
            }

            if (! Schema::hasColumn('claims', 'sla_response_breached')) {
                $table->boolean('sla_response_breached')->default(false)->after('resolved_at');
            }

            if (! Schema::hasColumn('claims', 'sla_resolution_breached')) {
                $table->boolean('sla_resolution_breached')->default(false)->after('sla_response_breached');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->dropColumn([
                'sla_response_due_at',
                'sla_resolution_due_at',
                'responded_at',
                'sla_response_breached',
                'sla_resolution_breached',
            ]);
        });
    }
};
