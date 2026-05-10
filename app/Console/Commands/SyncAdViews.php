<?php

namespace App\Console\Commands;

use App\Modules\Classifieds\Models\ClassifiedAd;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class SyncAdViews extends Command
{
    protected $signature = 'classifieds:sync-views';

    protected $description = 'Flush Redis view counts to PostgreSQL database';

    public function handle()
    {
        // Get all keys matching the buffer pattern
        // In production with millions of keys, utilize SCAN cursor instead of KEYS*
        $keys = Redis::keys('ad_views_buffer:*');

        if (empty($keys)) {
            return;
        }

        $count = 0;
        foreach ($keys as $key) {
            // Extract slug
            // Key format: laravel_database_ad_views_buffer:slug (prefix depends on config)
            $views = (int) Redis::get($key);

            if ($views > 0) {
                // Extract slug from key name (removing prefix 'ad_views_buffer:')
                $prefix = config('database.redis.options.prefix', '').'ad_views_buffer:';
                $slug = str_replace($prefix, '', $key);

                // Fallback if prefix config is tricky in local/prod differences:
                if ($slug === $key) {
                    $parts = explode(':', $key);
                    $slug = end($parts);
                }

                // Bulk update logic could be applied here, but for now simple update
                // Update DB: views_count = views_count + $views
                ClassifiedAd::where('slug', $slug)->increment('views_count', $views);

                // Remove the key or decrement (Atomic getset is better but let's delete)
                Redis::del($key);
                $count++;
            }
        }

        $this->info("Synced views for {$count} ads.");
    }
}
