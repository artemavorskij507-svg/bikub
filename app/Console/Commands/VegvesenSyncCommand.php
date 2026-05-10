<?php

namespace App\Console\Commands;

use App\Services\VegvesenCkanClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VegvesenSyncCommand extends Command
{
    protected $signature = 'vegvesen:sync {--query=} {--limit=50}';

    protected $description = 'Sync datasets/resources metadata from dataut.vegvesen.no (CKAN)';

    public function handle(VegvesenCkanClient $client): int
    {
        $query = $this->option('query') ?: config('vegvesen.default_query');
        $limit = (int) $this->option('limit');

        $this->info("Searching CKAN for: {$query}");
        $packages = $client->search($query, $limit);

        $count = 0;
        foreach ($packages as $pkg) {
            $key = $pkg['name'] ?? ($pkg['id'] ?? md5(json_encode($pkg)));
            DB::table('external_data_cache')->updateOrInsert(
                ['source' => 'vegvesen_ckan', 'cache_key' => $key],
                [
                    'payload' => json_encode($pkg, JSON_UNESCAPED_UNICODE),
                    'fetched_at' => now(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
            $count++;
        }

        $this->info("Stored {$count} dataset records.");

        return self::SUCCESS;
    }
}
