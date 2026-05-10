<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('handyman_services')) {
            Schema::create('handyman_services', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('category')->nullable();
                $table->string('pricing_mode')->default('HOURLY');
                $table->bigInteger('base_rate_minor')->default(0);
                $table->integer('estimated_duration_minutes')->nullable();
                $table->json('required_skills')->nullable();
                $table->boolean('is_active')->default(true);
                $table->json('metadata')->nullable();
                $table->timestamps();
            });

            return;
        }

        Schema::table('handyman_services', function (Blueprint $table) {
            if (! Schema::hasColumn('handyman_services', 'category')) {
                $table->string('category')->nullable()->after('description');
            }
            if (! Schema::hasColumn('handyman_services', 'pricing_mode')) {
                $table->string('pricing_mode')->default('HOURLY')->after('category');
            }
            if (! Schema::hasColumn('handyman_services', 'base_rate_minor')) {
                $table->bigInteger('base_rate_minor')->default(0)->after('pricing_mode');
            }
            if (! Schema::hasColumn('handyman_services', 'estimated_duration_minutes')) {
                $table->integer('estimated_duration_minutes')->nullable()->after('base_rate_minor');
            }
            if (! Schema::hasColumn('handyman_services', 'required_skills')) {
                $table->json('required_skills')->nullable()->after('estimated_duration_minutes');
            }
            if (! Schema::hasColumn('handyman_services', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('required_skills');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('handyman_services')) {
            return;
        }

        Schema::table('handyman_services', function (Blueprint $table) {
            $columns = [
                'category',
                'pricing_mode',
                'base_rate_minor',
                'estimated_duration_minutes',
                'required_skills',
                'is_active',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('handyman_services', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
