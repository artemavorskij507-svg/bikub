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
        Schema::table('support_tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('support_tickets', 'channel')) {
                $table->string('channel')->default('worker_lk')->after('source');
            }
            if (! Schema::hasColumn('support_tickets', 'metadata')) {
                $table->json('metadata')->nullable()->after('meta');
            }
            if (! Schema::hasColumn('support_tickets', 'resolved_by')) {
                $table->foreignId('resolved_by')->nullable()->after('resolved_at')->constrained('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            if (Schema::hasColumn('support_tickets', 'channel')) {
                $table->dropColumn('channel');
            }
            if (Schema::hasColumn('support_tickets', 'metadata')) {
                $table->dropColumn('metadata');
            }
            if (Schema::hasColumn('support_tickets', 'resolved_by')) {
                $table->dropForeign(['resolved_by']);
                $table->dropColumn('resolved_by');
            }
        });
    }
};
