<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        try {
            if (DB::getDriverName() === 'pgsql') {
                DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist;');
                DB::statement("
                    ALTER TABLE schedule_slots
                    ADD CONSTRAINT schedule_slots_no_overlap
                    EXCLUDE USING GIST (
                      zone_id WITH =,
                      kind WITH =,
                      tstzrange(start_at, end_at, '[)') WITH &&
                    )
                    WHERE (status = 'locked')
                ");
            }
        } catch (\Throwable $e) {
            // PostgreSQL specific constraint - skip if not available
            \Log::warning('Failed to create overlap guard constraint: '.$e->getMessage());
        }
    }

    public function down(): void
    {
        try {
            if (DB::getDriverName() === 'pgsql') {
                DB::statement('ALTER TABLE schedule_slots DROP CONSTRAINT IF EXISTS schedule_slots_no_overlap');
            }
        } catch (\Throwable $e) {
        }
    }
};
