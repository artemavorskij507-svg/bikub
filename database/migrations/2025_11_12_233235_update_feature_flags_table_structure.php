<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('feature_flags')) {
            return;
        }

        Schema::table('feature_flags', function (Blueprint $table) {
            // Add name column if it doesn't exist
            if (! Schema::hasColumn('feature_flags', 'name')) {
                $table->string('name')->nullable()->after('key');
            }

            // Add description column if it doesn't exist
            if (! Schema::hasColumn('feature_flags', 'description')) {
                $table->text('description')->nullable()->after('name');
            }

            // Add is_active column if it doesn't exist
            if (! Schema::hasColumn('feature_flags', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('description');
            }

            // Add default_on column if it doesn't exist
            if (! Schema::hasColumn('feature_flags', 'default_on')) {
                $table->boolean('default_on')->default(false)->after('is_active');
            }

            // Add rollout_percent column if it doesn't exist
            if (! Schema::hasColumn('feature_flags', 'rollout_percent')) {
                $table->unsignedSmallInteger('rollout_percent')->default(100)->after('default_on');
            }

            // Add rules column if it doesn't exist
            if (! Schema::hasColumn('feature_flags', 'rules')) {
                $table->json('rules')->nullable()->after('settings');
            }

            // Add starts_at column if it doesn't exist
            if (! Schema::hasColumn('feature_flags', 'starts_at')) {
                $table->timestampTz('starts_at')->nullable()->after('rules');
            }

            // Add ends_at column if it doesn't exist
            if (! Schema::hasColumn('feature_flags', 'ends_at')) {
                $table->timestampTz('ends_at')->nullable()->after('starts_at');
            }

            // Add owner_user_id column if it doesn't exist
            if (! Schema::hasColumn('feature_flags', 'owner_user_id')) {
                $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete()->after('ends_at');
            }

            // Update existing enabled column to match model (if needed)
            // The enabled column already exists, so we just ensure it has the right default
            if (Schema::hasColumn('feature_flags', 'enabled')) {
                // Check if default is set, if not, set it to false
                $table->boolean('enabled')->default(false)->change();
            }
        });

        // Update existing records: set name from key if name is null
        DB::table('feature_flags')
            ->whereNull('name')
            ->update(['name' => DB::raw('key')]);

        // Set is_active to true for all existing records if it was null
        DB::table('feature_flags')
            ->whereNull('is_active')
            ->update(['is_active' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('feature_flags')) {
            return;
        }

        Schema::table('feature_flags', function (Blueprint $table) {
            // Drop added columns
            $columnsToDrop = [
                'name',
                'description',
                'is_active',
                'default_on',
                'rollout_percent',
                'rules',
                'starts_at',
                'ends_at',
                'owner_user_id',
            ];

            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('feature_flags', $column)) {
                    if ($column === 'owner_user_id') {
                        $table->dropForeign(['owner_user_id']);
                    }
                    $table->dropColumn($column);
                }
            }
        });
    }
};
