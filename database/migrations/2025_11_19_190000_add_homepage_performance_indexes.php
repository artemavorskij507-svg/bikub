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
        // Индексы для service_categories
        if (Schema::hasTable('service_categories')) {
            Schema::table('service_categories', function (Blueprint $table) {
                if (! $this->hasIndex('service_categories', 'service_categories_sort_order_index')) {
                    $table->index('sort_order', 'service_categories_sort_order_index');
                }
                if (! $this->hasIndex('service_categories', 'service_categories_is_active_index')) {
                    $table->index('is_active', 'service_categories_is_active_index');
                }
                if (! $this->hasIndex('service_categories', 'service_categories_show_on_homepage_index')) {
                    $table->index('show_on_homepage', 'service_categories_show_on_homepage_index');
                }
            });
        }

        // Индексы для retail_stores
        if (Schema::hasTable('retail_stores')) {
            Schema::table('retail_stores', function (Blueprint $table) {
                if (! $this->hasIndex('retail_stores', 'retail_stores_is_active_index')) {
                    $table->index('is_active', 'retail_stores_is_active_index');
                }
                if (! $this->hasIndex('retail_stores', 'retail_stores_supports_grocery_delivery_index')) {
                    $table->index('supports_grocery_delivery', 'retail_stores_supports_grocery_delivery_index');
                }
            });
        }

        // Индексы для restaurants
        if (Schema::hasTable('restaurants')) {
            Schema::table('restaurants', function (Blueprint $table) {
                if (! $this->hasIndex('restaurants', 'restaurants_is_active_index')) {
                    $table->index('is_active', 'restaurants_is_active_index');
                }
                if (! $this->hasIndex('restaurants', 'restaurants_supports_food_delivery_index')) {
                    $table->index('supports_food_delivery', 'restaurants_supports_food_delivery_index');
                }
            });
        }

        // Индексы для delivery_orders (created_at уже есть, но проверим)
        if (Schema::hasTable('delivery_orders')) {
            Schema::table('delivery_orders', function (Blueprint $table) {
                if (! $this->hasIndex('delivery_orders', 'delivery_orders_created_at_index')) {
                    $table->index('created_at', 'delivery_orders_created_at_index');
                }
            });
        }

        // Индексы для handyman_services
        if (Schema::hasTable('handyman_services')) {
            Schema::table('handyman_services', function (Blueprint $table) {
                if (! $this->hasIndex('handyman_services', 'handyman_services_is_active_index')) {
                    $table->index('is_active', 'handyman_services_is_active_index');
                }
            });
        }

        // Индексы для errand_tasks
        if (Schema::hasTable('errand_tasks')) {
            Schema::table('errand_tasks', function (Blueprint $table) {
                if (! $this->hasIndex('errand_tasks', 'errand_tasks_created_at_index')) {
                    $table->index('created_at', 'errand_tasks_created_at_index');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_categories', function (Blueprint $table) {
            $table->dropIndex('service_categories_sort_order_index');
            $table->dropIndex('service_categories_is_active_index');
            $table->dropIndex('service_categories_show_on_homepage_index');
        });

        Schema::table('retail_stores', function (Blueprint $table) {
            $table->dropIndex('retail_stores_is_active_index');
            $table->dropIndex('retail_stores_supports_grocery_delivery_index');
        });

        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropIndex('restaurants_is_active_index');
            $table->dropIndex('restaurants_supports_food_delivery_index');
        });

        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->dropIndex('delivery_orders_created_at_index');
        });

        Schema::table('handyman_services', function (Blueprint $table) {
            $table->dropIndex('handyman_services_is_active_index');
        });

        Schema::table('errand_tasks', function (Blueprint $table) {
            $table->dropIndex('errand_tasks_created_at_index');
        });
    }

    /**
     * Check if index exists
     */
    protected function hasIndex(string $table, string $indexName): bool
    {
        $indexes = \Illuminate\Support\Facades\DB::select(
            'SELECT indexname FROM pg_indexes WHERE tablename = ? AND indexname = ?',
            [$table, $indexName]
        );

        return count($indexes) > 0;
    }
};
