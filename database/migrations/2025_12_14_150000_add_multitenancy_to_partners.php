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
            // Додаємо поля для multitenancy
            if (! Schema::hasColumn('partners', 'domain')) {
                $table->string('domain')->unique()->nullable()->after('name');
            }
            if (! Schema::hasColumn('partners', 'database')) {
                $table->string('database')->unique()->nullable()->after('domain');
            }
            if (! Schema::hasColumn('partners', 'subscription_tier')) {
                $table->string('subscription_tier')->default('basic')->after('is_active');
            }
            if (! Schema::hasColumn('partners', 'subscription_ends_at')) {
                $table->timestamp('subscription_ends_at')->nullable()->after('subscription_tier');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->dropUnique(['domain']);
            $table->dropUnique(['database']);
            $table->dropColumn(['domain', 'database', 'subscription_tier', 'subscription_ends_at']);
        });
    }
};
