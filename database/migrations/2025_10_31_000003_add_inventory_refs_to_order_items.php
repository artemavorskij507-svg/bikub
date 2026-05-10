<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('order_items', 'product_id')) {
                $table->unsignedBigInteger('product_id')->nullable()->after('service_type_id');
                // products table may not exist yet; keep as nullable FK when available
            }
            if (! Schema::hasColumn('order_items', 'store_id')) {
                $table->foreignId('store_id')->nullable()->constrained('retail_stores')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'store_id')) {
                $table->dropConstrainedForeignId('store_id');
            }
            if (Schema::hasColumn('order_items', 'product_id')) {
                $table->dropColumn('product_id');
            }
        });
    }
};
