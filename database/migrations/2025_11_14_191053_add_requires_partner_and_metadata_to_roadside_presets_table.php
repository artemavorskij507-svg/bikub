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
        Schema::table('roadside_presets', function (Blueprint $table) {
            if (! Schema::hasColumn('roadside_presets', 'requires_partner')) {
                $table->boolean('requires_partner')->default(false)->after('base_price');
            }
            if (! Schema::hasColumn('roadside_presets', 'metadata')) {
                $table->json('metadata')->nullable()->after('is_active');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roadside_presets', function (Blueprint $table) {
            $table->dropColumn(['requires_partner', 'metadata']);
        });
    }
};
