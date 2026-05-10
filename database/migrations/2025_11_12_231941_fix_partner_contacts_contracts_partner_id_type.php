<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix partner_contacts.partner_id
        if (Schema::hasTable('partner_contacts')) {
            $columnType = DB::selectOne("
                SELECT data_type 
                FROM information_schema.columns 
                WHERE table_name = 'partner_contacts' 
                AND column_name = 'partner_id'
            ");

            if ($columnType && $columnType->data_type === 'uuid') {
                // Drop foreign key if exists
                try {
                    DB::statement('ALTER TABLE partner_contacts DROP CONSTRAINT IF EXISTS partner_contacts_partner_id_foreign');
                } catch (\Exception $e) {
                    // Ignore if constraint doesn't exist
                }

                // Change column type from uuid to bigint
                // First, clear the column if it has data (since we can't convert uuid to bigint)
                DB::statement('UPDATE partner_contacts SET partner_id = NULL WHERE partner_id IS NOT NULL');

                // Drop and recreate the column
                DB::statement('ALTER TABLE partner_contacts DROP COLUMN IF EXISTS partner_id');
                DB::statement('ALTER TABLE partner_contacts ADD COLUMN partner_id BIGINT');
                DB::statement('CREATE INDEX IF NOT EXISTS partner_contacts_partner_id_index ON partner_contacts(partner_id)');
            }
        }

        // Fix partner_contracts.partner_id
        if (Schema::hasTable('partner_contracts')) {
            $columnType = DB::selectOne("
                SELECT data_type 
                FROM information_schema.columns 
                WHERE table_name = 'partner_contracts' 
                AND column_name = 'partner_id'
            ");

            if ($columnType && $columnType->data_type === 'uuid') {
                // Drop foreign key if exists
                try {
                    DB::statement('ALTER TABLE partner_contracts DROP CONSTRAINT IF EXISTS partner_contracts_partner_id_foreign');
                } catch (\Exception $e) {
                    // Ignore if constraint doesn't exist
                }

                // Change column type from uuid to bigint
                // First, clear the column if it has data (since we can't convert uuid to bigint)
                DB::statement('UPDATE partner_contracts SET partner_id = NULL WHERE partner_id IS NOT NULL');

                // Drop and recreate the column
                DB::statement('ALTER TABLE partner_contracts DROP COLUMN IF EXISTS partner_id');
                DB::statement('ALTER TABLE partner_contracts ADD COLUMN partner_id BIGINT');
                DB::statement('CREATE INDEX IF NOT EXISTS partner_contracts_partner_id_index ON partner_contracts(partner_id)');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: This rollback is not safe if data exists
        // We're not implementing rollback to avoid data loss
    }
};
