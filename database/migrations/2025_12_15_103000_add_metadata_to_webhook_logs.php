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
        Schema::table('webhook_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('webhook_logs', 'metadata')) {
                $table->json('metadata')->nullable()->after('payment_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('webhook_logs', function (Blueprint $table) {
            if (Schema::hasColumn('webhook_logs', 'metadata')) {
                $table->dropColumn('metadata');
            }
        });
    }
};
