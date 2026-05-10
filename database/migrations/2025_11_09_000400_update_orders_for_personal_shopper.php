<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'store_id')) {
                $table->foreignId('store_id')->nullable()->after('user_id')->constrained('stores')->nullOnDelete();
            }

            if (! Schema::hasColumn('orders', 'estimated_total')) {
                $table->unsignedInteger('estimated_total')->nullable()->after('total_amount')->comment('In øre');
            }

            if (! Schema::hasColumn('orders', 'buffer_total')) {
                $table->unsignedInteger('buffer_total')->nullable()->after('estimated_total')->comment('In øre');
            }

            if (! Schema::hasColumn('orders', 'actual_total')) {
                $table->unsignedInteger('actual_total')->nullable()->after('buffer_total')->comment('In øre');
            }

            if (! Schema::hasColumn('orders', 'payment_intent_id')) {
                $table->string('payment_intent_id')->nullable()->after('payment_status')->index();
            }

            if (! Schema::hasColumn('orders', 'receipt_url')) {
                $table->string('receipt_url')->nullable()->after('payment_intent_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'receipt_url')) {
                $table->dropColumn('receipt_url');
            }

            if (Schema::hasColumn('orders', 'payment_intent_id')) {
                $table->dropIndex(['payment_intent_id']);
                $table->dropColumn('payment_intent_id');
            }

            if (Schema::hasColumn('orders', 'actual_total')) {
                $table->dropColumn('actual_total');
            }

            if (Schema::hasColumn('orders', 'buffer_total')) {
                $table->dropColumn('buffer_total');
            }

            if (Schema::hasColumn('orders', 'estimated_total')) {
                $table->dropColumn('estimated_total');
            }

            if (Schema::hasColumn('orders', 'store_id')) {
                $table->dropConstrainedForeignId('store_id');
            }
        });
    }
};
