<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('care_order_details', function (Blueprint $table) {
            // Add indexes for analytics queries if they don't exist
            if (! $this->indexExists('care_order_details', 'care_order_details_care_status_index')) {
                $table->index('care_status', 'care_order_details_care_status_index');
            }
            if (! $this->indexExists('care_order_details', 'care_order_details_scheduled_start_at_index')) {
                $table->index('scheduled_start_at', 'care_order_details_scheduled_start_at_index');
            }
            if (! $this->indexExists('care_order_details', 'care_order_details_care_service_id_index')) {
                $table->index('care_service_id', 'care_order_details_care_service_id_index');
            }
        });

        Schema::table('visit_reports', function (Blueprint $table) {
            if (! $this->indexExists('visit_reports', 'visit_reports_care_order_details_id_index')) {
                $table->index('care_order_details_id', 'visit_reports_care_order_details_id_index');
            }
        });

        Schema::table('client_profiles', function (Blueprint $table) {
            if (! $this->indexExists('client_profiles', 'client_profiles_city_index')) {
                $table->index('city', 'client_profiles_city_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('care_order_details', function (Blueprint $table) {
            $table->dropIndex('care_order_details_care_status_index');
            $table->dropIndex('care_order_details_scheduled_start_at_index');
            $table->dropIndex('care_order_details_care_service_id_index');
        });

        Schema::table('visit_reports', function (Blueprint $table) {
            $table->dropIndex('visit_reports_care_order_details_id_index');
        });

        Schema::table('client_profiles', function (Blueprint $table) {
            $table->dropIndex('client_profiles_city_index');
        });
    }

    protected function indexExists(string $table, string $index): bool
    {
        try {
            $connection = Schema::getConnection();
            $doctrineSchemaManager = $connection->getDoctrineSchemaManager();
            $doctrineTable = $doctrineSchemaManager->introspectTable($table);

            return $doctrineTable->hasIndex($index);
        } catch (\Exception $e) {
            return false;
        }
    }
};
