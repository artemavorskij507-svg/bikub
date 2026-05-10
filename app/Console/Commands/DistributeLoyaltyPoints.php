<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class DistributeLoyaltyPoints extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'loyalty:distribute 
                            {--points=10 : Кількість балів для розповсюдження}
                            {--user= : Email користувача (якщо не вказано - всім)}
                            {--reason= : Причина розповсюдження}
                            {--exclude-zero : Виключити користувачів без балів}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Розповсюджити бали лояльності користувачам';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $points = (int) $this->option('points');
        $userEmail = $this->option('user');
        $reason = $this->option('reason') ?? 'Промо-бали від системи';
        $excludeZero = $this->option('exclude-zero');

        if ($points <= 0) {
            $this->error('❌ Кількість балів має бути більшою за 0');

            return 1;
        }

        $this->info("📊 Розповсюдження {$points} балів...\n");

        $query = User::query();

        if ($userEmail) {
            $query->where('email', $userEmail);
        }

        $users = $query->get();

        if ($users->isEmpty()) {
            $this->warn('⚠️ Користувачів не знайдено');

            return 1;
        }

        $this->info('👥 Знайдено користувачів: '.$users->count()."\n");

        $count = 0;
        $skipped = 0;

        $progressBar = $this->output->createProgressBar($users->count());
        $progressBar->start();

        foreach ($users as $user) {
            $balance = $user->getOrCreateLoyaltyBalance();

            if ($excludeZero && $balance->points === 0) {
                $skipped++;
                $progressBar->advance();

                continue;
            }

            $balance->addPoints($points, $reason, 'SystemDistribution', 0);
            $count++;
            $progressBar->advance();
        }

        $progressBar->finish();

        $this->info("\n\n✅ Операція завершена!");
        $this->info("  • Оброблено: {$count}");
        if ($skipped > 0) {
            $this->info("  • Пропущено: {$skipped}");
        }
        $this->info('  • Всього балів розповсюджено: '.($count * $points));

        return 0;
    }
}
