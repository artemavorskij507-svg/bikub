<?php

namespace App\Console\Commands;

use App\Services\KassalSyncService;
use Exception;
use Illuminate\Console\Command;

class KassalSyncCommand extends Command
{
    protected $signature = 'kassal:sync 
                            {--stores-only : Sync only stores}
                            {--products-only : Sync only products}';

    protected $description = 'Sync stores & products from Kassal API';

    public function handle(KassalSyncService $sync): int
    {
        $this->info('🔄 Starting Kassal synchronization...');

        try {
            if ($this->option('stores-only')) {
                $count = $sync->syncStores();
                $this->info("✅ Synced {$count} stores from Kassal.");

                return Command::SUCCESS;
            }

            if ($this->option('products-only')) {
                $count = $sync->syncProducts();
                $this->info("✅ Synced {$count} products from Kassal.");

                return Command::SUCCESS;
            }

            $result = $sync->syncAll();
            $this->info('✅ Kassal sync completed successfully!');
            $this->table(
                ['Type', 'Count'],
                [
                    ['Stores', $result['stores']],
                    ['Products', $result['products']],
                ]
            );

            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->error("❌ Error: {$e->getMessage()}");
            $this->warn('Make sure KASSAL_API_KEY is set in your .env file.');

            return Command::FAILURE;
        }
    }
}
