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
        Schema::table('orders', function (Blueprint $table) {
            // Додаємо roadside_partner_id
            if (! Schema::hasColumn('orders', 'roadside_partner_id')) {
                $table->foreignId('roadside_partner_id')
                    ->nullable()
                    ->after('assigned_to')
                    ->constrained('partners')
                    ->nullOnDelete();
            }

            // Додаємо roadside_partner_metadata (доп. інформація)
            if (! Schema::hasColumn('orders', 'roadside_partner_metadata')) {
                $table->json('roadside_partner_metadata')->nullable()->after('roadside_partner_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'roadside_partner_metadata')) {
                $table->dropColumn('roadside_partner_metadata');
            }

            if (Schema::hasColumn('orders', 'roadside_partner_id')) {
                $table->dropForeign(['roadside_partner_id']);
                $table->dropColumn('roadside_partner_id');
            }
        });
    }
};
