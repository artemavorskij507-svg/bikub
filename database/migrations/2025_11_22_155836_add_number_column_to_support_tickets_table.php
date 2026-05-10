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
            if (! Schema::hasColumn('support_tickets', 'number')) {
                $table->string('number')->nullable()->unique()->after('id');
            }
            if (! Schema::hasColumn('support_tickets', 'source')) {
                $table->string('source')->default('web_form')->after('channel');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            if (Schema::hasColumn('support_tickets', 'number')) {
                $table->dropColumn('number');
            }
            if (Schema::hasColumn('support_tickets', 'source')) {
                $table->dropColumn('source');
            }
        });
    }
};
