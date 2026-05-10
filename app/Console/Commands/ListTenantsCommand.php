<?php

namespace App\Console\Commands;

use App\Models\Partner;
use Illuminate\Console\Command;

class ListTenantsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Показати список всіх тенантів (партнерів)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $partners = Partner::all();

        if ($partners->isEmpty()) {
            $this->info('📭 Немає створених тенантів');

            return self::SUCCESS;
        }

        $this->info('📋 Список всіх тенантів:');
        $this->line('');

        $this->table(
            ['ID', 'Назва', 'Домен', 'Рівень', 'Статус', 'Створено'],
            $partners->map(fn ($p) => [
                $p->id,
                $p->name,
                $p->domain,
                $p->subscription_tier,
                $p->is_active ? '✅ Активна' : '❌ Неактивна',
                $p->created_at->format('Y-m-d H:i'),
            ])->toArray()
        );

        $this->line('');
        $this->info("📊 Всього тенантів: {$partners->count()}");

        return self::SUCCESS;
    }
}
