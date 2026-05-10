<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'weight_kg')) {
                $table->decimal('weight_kg', 8, 2)->nullable();
            }

            if (! Schema::hasColumn('products', 'volume_m3')) {
                $table->decimal('volume_m3', 8, 3)->nullable();
            }

            if (! Schema::hasColumn('products', 'dimensions')) {
                $table->json('dimensions')->nullable();
            }

            if (! Schema::hasColumn('products', 'unit')) {
                $table->string('unit')->nullable();
            }

            if (! Schema::hasColumn('products', 'canonical_name')) {
                $table->string('canonical_name')->nullable()->index();
            }

            if (! Schema::hasColumn('products', 'source_file')) {
                $table->string('source_file')->nullable();
            }
        });

        Schema::table('service_types', function (Blueprint $table) {
            if (! Schema::hasColumn('service_types', 'canonical_code')) {
                $table->string('canonical_code')->nullable()->index();
            }

            if (! Schema::hasColumn('service_types', 'default_pricing_group')) {
                $table->string('default_pricing_group')->nullable();
            }
        });

        Schema::table('service_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('service_categories', 'canonical_code')) {
                $table->string('canonical_code')->nullable()->index();
            }

            if (! Schema::hasColumn('service_categories', 'default_pricing_group')) {
                $table->string('default_pricing_group')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'weight_kg')) {
                $table->dropColumn('weight_kg');
            }

            if (Schema::hasColumn('products', 'volume_m3')) {
                $table->dropColumn('volume_m3');
            }

            if (Schema::hasColumn('products', 'dimensions')) {
                $table->dropColumn('dimensions');
            }

            if (Schema::hasColumn('products', 'unit')) {
                $table->dropColumn('unit');
            }

            if (Schema::hasColumn('products', 'canonical_name')) {
                $table->dropIndex('products_canonical_name_index');
                $table->dropColumn('canonical_name');
            }

            if (Schema::hasColumn('products', 'source_file')) {
                $table->dropColumn('source_file');
            }
        });

        Schema::table('service_types', function (Blueprint $table) {
            if (Schema::hasColumn('service_types', 'canonical_code')) {
                $table->dropIndex('service_types_canonical_code_index');
                $table->dropColumn('canonical_code');
            }

            if (Schema::hasColumn('service_types', 'default_pricing_group')) {
                $table->dropColumn('default_pricing_group');
            }
        });

        Schema::table('service_categories', function (Blueprint $table) {
            if (Schema::hasColumn('service_categories', 'canonical_code')) {
                $table->dropIndex('service_categories_canonical_code_index');
                $table->dropColumn('canonical_code');
            }

            if (Schema::hasColumn('service_categories', 'default_pricing_group')) {
                $table->dropColumn('default_pricing_group');
            }
        });
    }
};
