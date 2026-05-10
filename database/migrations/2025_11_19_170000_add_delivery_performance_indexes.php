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
        // Indexes for delivery_orders
        if (Schema::hasTable('delivery_orders')) {
            Schema::table('delivery_orders', function (Blueprint $table) {
                if (! $this->hasIndex('delivery_orders', 'delivery_orders_order_id_idx')) {
                    $table->index('order_id', 'delivery_orders_order_id_idx');
                }

                if (! $this->hasIndex('delivery_orders', 'delivery_orders_type_status_idx')) {
                    $table->index(['type', 'tracking_status'], 'delivery_orders_type_status_idx');
                }

                if (! $this->hasIndex('delivery_orders', 'delivery_orders_courier_status_idx')) {
                    $table->index(['courier_id', 'tracking_status'], 'delivery_orders_courier_status_idx');
                }

                if (! $this->hasIndex('delivery_orders', 'delivery_orders_eta_idx')) {
                    $table->index('eta', 'delivery_orders_eta_idx');
                }

                if (! $this->hasIndex('delivery_orders', 'delivery_orders_tracking_token_idx')) {
                    $table->index('tracking_token', 'delivery_orders_tracking_token_idx');
                }
            });
        }

        // Indexes for orders
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (! $this->hasIndex('orders', 'orders_user_id_status_idx')) {
                    $table->index(['user_id', 'status'], 'orders_user_id_status_idx');
                }

                if (! $this->hasIndex('orders', 'orders_created_at_idx')) {
                    $table->index('created_at', 'orders_created_at_idx');
                }
            });
        }

        // Indexes for geo_zones
        if (Schema::hasTable('geo_zones')) {
            Schema::table('geo_zones', function (Blueprint $table) {
                if (! $this->hasIndex('geo_zones', 'geo_zones_slug_idx')) {
                    $table->index('slug', 'geo_zones_slug_idx');
                }

                if (! $this->hasIndex('geo_zones', 'geo_zones_type_active_idx')) {
                    $table->index(['type', 'is_active'], 'geo_zones_type_active_idx');
                }
            });
        }

        // Indexes for pricing_rules
        if (Schema::hasTable('pricing_rules')) {
            Schema::table('pricing_rules', function (Blueprint $table) {
                if (! $this->hasIndex('pricing_rules', 'pricing_rules_service_type_active_idx')) {
                    $table->index(['service_type_id', 'is_active'], 'pricing_rules_service_type_active_idx');
                }

                if (! $this->hasIndex('pricing_rules', 'pricing_rules_geo_zone_idx')) {
                    // Note: geo_zone_id is in conditions JSON, so we can't index it directly
                    // But we can index service_type_id + is_active for faster lookups
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('delivery_orders')) {
            Schema::table('delivery_orders', function (Blueprint $table) {
                $table->dropIndex('delivery_orders_order_id_idx');
                $table->dropIndex('delivery_orders_type_status_idx');
                $table->dropIndex('delivery_orders_courier_status_idx');
                $table->dropIndex('delivery_orders_eta_idx');
                $table->dropIndex('delivery_orders_tracking_token_idx');
            });
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropIndex('orders_user_id_status_idx');
                $table->dropIndex('orders_created_at_idx');
            });
        }

        if (Schema::hasTable('geo_zones')) {
            Schema::table('geo_zones', function (Blueprint $table) {
                $table->dropIndex('geo_zones_slug_idx');
                $table->dropIndex('geo_zones_type_active_idx');
            });
        }

        if (Schema::hasTable('pricing_rules')) {
            Schema::table('pricing_rules', function (Blueprint $table) {
                $table->dropIndex('pricing_rules_service_type_active_idx');
            });
        }
    }

    /**
     * Check if an index exists on a table.
     */
    protected function hasIndex(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $doctrineSchemaManager = $connection->getDoctrineSchemaManager();
        $doctrineTable = $doctrineSchemaManager->introspectTable($table);

        return $doctrineTable->hasIndex($indexName);
    }
};
