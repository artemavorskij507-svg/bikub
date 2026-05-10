<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('executor_shifts')) {
            Schema::create('executor_shifts', function (Blueprint $table): void {
                $table->id();
                $table->string('organization_id', 64)->nullable()->index();
                $table->string('tenant_id', 64)->nullable()->index();
                $table->unsignedBigInteger('executor_id')->index();
                $table->unsignedTinyInteger('day_of_week')->nullable()->index();
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->date('shift_date')->nullable()->index();
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->boolean('is_available')->default(true)->index();
                $table->string('timezone', 64)->default('UTC');
                $table->timestamps();
            });

            return;
        }

        Schema::table('executor_shifts', function (Blueprint $table): void {
            if (! Schema::hasColumn('executor_shifts', 'organization_id')) {
                $table->string('organization_id', 64)->nullable()->after('id')->index();
            }
            if (! Schema::hasColumn('executor_shifts', 'tenant_id')) {
                $table->string('tenant_id', 64)->nullable()->after('organization_id')->index();
            }
            if (! Schema::hasColumn('executor_shifts', 'day_of_week')) {
                $table->unsignedTinyInteger('day_of_week')->nullable()->after('executor_id')->index();
            }
            if (! Schema::hasColumn('executor_shifts', 'start_time')) {
                $table->time('start_time')->nullable()->after('day_of_week');
            }
            if (! Schema::hasColumn('executor_shifts', 'end_time')) {
                $table->time('end_time')->nullable()->after('start_time');
            }
            if (! Schema::hasColumn('executor_shifts', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('is_available')->index();
            }
            if (! Schema::hasColumn('executor_shifts', 'timezone')) {
                $table->string('timezone', 64)->default('UTC')->after('is_active');
            }
        });
    }

    public function down(): void
    {
        // additive migration: do not drop existing ops table
    }
};
