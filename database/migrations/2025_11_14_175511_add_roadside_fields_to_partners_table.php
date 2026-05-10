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
        Schema::table('partners', function (Blueprint $table) {
            // Добавляем поля только если их еще нет
            if (! Schema::hasColumn('partners', 'emergency_distance_km')) {
                $table->integer('emergency_distance_km')->nullable()->after('type');
            }
            if (! Schema::hasColumn('partners', 'emergency_price_base')) {
                $table->decimal('emergency_price_base', 10, 2)->nullable()->after('emergency_distance_km');
            }
            if (! Schema::hasColumn('partners', 'emergency_price_per_km')) {
                $table->decimal('emergency_price_per_km', 10, 2)->nullable()->after('emergency_price_base');
            }
            if (! Schema::hasColumn('partners', 'is_available')) {
                $table->boolean('is_available')->default(true)->after('active');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->dropColumn([
                'emergency_distance_km',
                'emergency_price_base',
                'emergency_price_per_km',
                'is_available',
            ]);
        });
    }
};
