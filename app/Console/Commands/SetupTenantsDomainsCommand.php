<?php

namespace App\Console\Commands;

use App\Models\Partner;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SetupTenantsDomainsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:setup-domains {--base-domain=glfbikube.local : Базовий домен}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Автоматично налаштувати домени для всіх партнерів';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $baseDomain = $this->option('base-domain');
        $partners = Partner::whereNull('domain')->orWhere('domain', '')->get();

        if ($partners->isEmpty()) {
            $this->info('✅ Всі партнери вже мають домени!');

            return self::SUCCESS;
        }

        $this->info("🔧 Налаштування доменів для {$partners->count()} партнерів...");
        $this->line("📍 Базовий домен: {$baseDomain}");
        $this->line('');

        $updated = 0;
        foreach ($partners as $partner) {
            // Генеруємо субдомен з ID та slug від назви
            $slug = Str::slug(str_replace([' ', 'AS', 'Ltd', 'AB'], '', $partner->name));
            $slug = substr($slug, 0, 20); // Обмежуємо довжину
            $subdomain = "{$slug}-{$partner->id}";

            $domain = "{$subdomain}.{$baseDomain}";
            $database = 'tenant_'.str_replace(['.', '-'], '_', $subdomain);

            try {
                $partner->update([
                    'domain' => $domain,
                    'database' => $database,
                ]);

                $updated++;
                $this->line("✅ {$partner->name}");
                $this->line("   └─ {$domain}");

            } catch (\Exception $e) {
                $this->error("❌ {$partner->name}: {$e->getMessage()}");
            }
        }

        $this->line('');
        $this->info("🎉 Оновлено {$updated} партнерів!");

        return self::SUCCESS;
    }
}
