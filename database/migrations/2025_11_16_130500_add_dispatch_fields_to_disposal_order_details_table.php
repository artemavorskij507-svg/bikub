<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disposal_order_details', function (Blueprint $table) {
            $table->foreignId('eco_team_id')
                ->nullable()
                ->after('eco_partner_hint_id')
                ->constrained('eco_teams')
                ->nullOnDelete();

            $table->foreignId('eco_partner_id')
                ->nullable()
                ->after('eco_team_id')
                ->constrained('disposal_partners')
                ->nullOnDelete();

            $table->string('eco_status')
                ->default('pending')
                ->after('eco_partner_id');
        });
    }

    public function down(): void
    {
        Schema::table('disposal_order_details', function (Blueprint $table) {
            $table->dropForeign(['eco_team_id']);
            $table->dropForeign(['eco_partner_id']);
            $table->dropColumn(['eco_team_id', 'eco_partner_id', 'eco_status']);
        });
    }
};
