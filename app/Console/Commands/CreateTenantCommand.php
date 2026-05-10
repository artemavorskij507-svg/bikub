<?php

namespace App\Console\Commands;

use App\Models\Partner;
use App\Models\PartnerSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateTenantCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:create 
                            {name : Назва партнера}
                            {domain : Домен партнера (наприклад: partner1.glfbikube.com)}
                            {--tier=basic : Рівень підписки (basic, professional, enterprise)}
                            {--type=towing_service : Тип партнера}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Створити нового тенанта (партнера)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->argument('name');
        $domain = $this->argument('domain');
        $tier = $this->option('tier');
        $type = $this->option('type');

        // Генеруємо ім'я БД з домену
        $database = str_replace(['.', '-'], '_', $domain);
        $database = 'tenant_'.$database;

        try {
            $this->info('📋 Створення тенанта...');
            $this->line("  Назва: {$name}");
            $this->line("  Домен: {$domain}");
            $this->line("  База даних: {$database}");
            $this->line("  Рівень: {$tier}");
            $this->line("  Тип: {$type}");

            // Перевіряємо, чи домен вже існує
            if (Partner::where('domain', $domain)->exists()) {
                $this->error("❌ Партнер з доменом '{$domain}' вже існує!");

                return self::FAILURE;
            }

            // Створюємо партнера
            $partner = Partner::create([
                'name' => $name,
                'slug' => Str::slug($name),
                'type' => $type,
                'domain' => $domain,
                'database' => $database,
                'subscription_tier' => $tier,
                'is_active' => true,
            ]);

            $this->info("✅ Партнер створено! ID: {$partner->id}");

            // Створюємо налаштування партнера
            PartnerSettings::create([
                'partner_id' => $partner->id,
                'timezone' => 'Europe/Kyiv',
                'language' => 'uk',
                'api_key' => hash('sha256', $partner->id.$domain.now()),
            ]);

            $this->info('✅ Налаштування створено!');

            // Виводимо інформацію
            $this->line('');
            $this->info('📊 Інформація про тенанта:');
            $this->table(
                ['Поле', 'Значення'],
                [
                    ['ID', $partner->id],
                    ['Назва', $partner->name],
                    ['Домен', $partner->domain],
                    ['База даних', $partner->database],
                    ['Рівень', $partner->subscription_tier],
                    ['API ключ', PartnerSettings::where('partner_id', $partner->id)->first()?->api_key],
                ]
            );

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Помилка: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
