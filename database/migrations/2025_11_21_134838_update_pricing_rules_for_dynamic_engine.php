<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pricing_rules', function (Blueprint $table) {
            if (! Schema::hasColumn('pricing_rules', 'slug')) {
                $table->string('slug')->nullable()->unique()->after('name');
            }

            if (! Schema::hasColumn('pricing_rules', 'type')) {
                $table->string('type', 32)->default('base_fee')->after('slug');
            }

            if (! Schema::hasColumn('pricing_rules', 'value')) {
                $table->decimal('value', 10, 2)->nullable()->after('type');
            }

            if (! Schema::hasColumn('pricing_rules', 'unit')) {
                $table->string('unit')->nullable()->after('currency');
            }

            if (! Schema::hasColumn('pricing_rules', 'applies_to')) {
                $table->json('applies_to')->nullable()->after('unit');
            }

            if (! Schema::hasColumn('pricing_rules', 'conditions')) {
                $table->json('conditions')->nullable()->after('applies_to');
            }

            if (! Schema::hasColumn('pricing_rules', 'priority')) {
                $table->integer('priority')->default(100)->after('conditions');
            }

            if (! Schema::hasColumn('pricing_rules', 'active')) {
                $table->boolean('active')->default(true)->after('priority');
            }

            if (! Schema::hasColumn('pricing_rules', 'meta')) {
                $table->json('meta')->nullable()->after('active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pricing_rules', function (Blueprint $table) {
            if (Schema::hasColumn('pricing_rules', 'meta')) {
                $table->dropColumn('meta');
            }

            if (Schema::hasColumn('pricing_rules', 'active')) {
                $table->dropColumn('active');
            }

            if (Schema::hasColumn('pricing_rules', 'priority')) {
                $table->dropColumn('priority');
            }

            if (Schema::hasColumn('pricing_rules', 'conditions')) {
                $table->dropColumn('conditions');
            }

            if (Schema::hasColumn('pricing_rules', 'applies_to')) {
                $table->dropColumn('applies_to');
            }

            if (Schema::hasColumn('pricing_rules', 'unit')) {
                $table->dropColumn('unit');
            }

            if (Schema::hasColumn('pricing_rules', 'value')) {
                $table->dropColumn('value');
            }

            if (Schema::hasColumn('pricing_rules', 'type')) {
                $table->dropColumn('type');
            }

            if (Schema::hasColumn('pricing_rules', 'slug')) {
                $table->dropUnique('pricing_rules_slug_unique');
                $table->dropColumn('slug');
            }
        });
    }
};
