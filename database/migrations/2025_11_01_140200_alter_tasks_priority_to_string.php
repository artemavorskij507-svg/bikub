<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // If priority is integer, change it to varchar to accept 'low','normal','high','urgent'
        // Use raw SQL for Postgres ALTER TYPE safely
        try {
            DB::statement("ALTER TABLE tasks ALTER COLUMN priority TYPE varchar(255)
                USING CASE
                    WHEN priority IS NULL THEN 'normal'
                    WHEN priority = 0 THEN 'low'
                    WHEN priority = 1 THEN 'normal'
                    WHEN priority = 2 THEN 'high'
                    WHEN priority = 3 THEN 'urgent'
                    ELSE priority::text
                END");
        } catch (\Throwable $e) {
            // ignore if already varchar
        }
    }

    public function down(): void
    {
        // Optional: map back to integer (normal=1)
        try {
            DB::statement("ALTER TABLE tasks ALTER COLUMN priority TYPE integer USING CASE
                WHEN priority = 'low' THEN 0
                WHEN priority = 'normal' THEN 1
                WHEN priority = 'high' THEN 2
                WHEN priority = 'urgent' THEN 3
                ELSE NULL
            END");
        } catch (\Throwable $e) {
            // ignore
        }
    }
};
