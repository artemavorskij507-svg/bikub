<?php

namespace App\Filament\Resources\PriceEstimateLogResource\Pages;

use App\Filament\Resources\PriceEstimateLogResource;
use App\Filament\Resources\PriceEstimateLogResource\Widgets\PriceEstimateLogsOverviewWidget;
use App\Models\PriceEstimateLog;
use Carbon\Carbon;
use Filament\Pages\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class ListPriceEstimateLogs extends ListRecords
{
    protected static string $resource = PriceEstimateLogResource::class;

    public function mount(): void
    {
        $this->ensureLocalPriceEstimateLogsSchema();
        $this->seedLocalPriceEstimateLogsIfEmpty();

        parent::mount();
    }

    protected function getActions(): array
    {
        return [
            Actions\Action::make('clear_old')
                ->label('Очистить старые логи')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Очистить старые логи?')
                ->modalSubheading('Будут удалены логи старше 90 дней. Это действие нельзя отменить.')
                ->action(function () {
                    $deleted = PriceEstimateLog::where('created_at', '<', Carbon::now()->subDays(90))->delete();

                    \Filament\Notifications\Notification::make()
                        ->title('Логи очищены')
                        ->body("Удалено записей: {$deleted}")
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PriceEstimateLogsOverviewWidget::class,
        ];
    }

    public function getTabs(): array
    {
        $today = Carbon::today();
        $thisWeek = Carbon::now()->startOfWeek();
        $thisMonth = Carbon::now()->startOfMonth();

        return [
            'all' => Tab::make('Все')
                ->badge(PriceEstimateLog::count()),
            'today' => Tab::make('Сегодня')
                ->badge(PriceEstimateLog::whereDate('created_at', $today)->count())
                ->modifyQueryUsing(fn ($query) => $query->whereDate('created_at', $today)),
            'this_week' => Tab::make('Эта неделя')
                ->badge(PriceEstimateLog::where('created_at', '>=', $thisWeek)->count())
                ->modifyQueryUsing(fn ($query) => $query->where('created_at', '>=', $thisWeek)),
            'this_month' => Tab::make('Этот месяц')
                ->badge(PriceEstimateLog::where('created_at', '>=', $thisMonth)->count())
                ->modifyQueryUsing(fn ($query) => $query->where('created_at', '>=', $thisMonth)),
            'with_user' => Tab::make('Авторизованные')
                ->badge(PriceEstimateLog::whereNotNull('user_id')->count())
                ->modifyQueryUsing(fn ($query) => $query->whereNotNull('user_id')),
            'guests' => Tab::make('Гости')
                ->badge(PriceEstimateLog::whereNull('user_id')->count())
                ->modifyQueryUsing(fn ($query) => $query->whereNull('user_id')),
        ];
    }

    protected function ensureLocalPriceEstimateLogsSchema(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        if (Schema::hasTable('price_estimate_logs')) {
            return;
        }

        Schema::create('price_estimate_logs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->index();
            $table->string('service_type')->nullable()->index();
            $table->string('zone')->nullable()->index();
            $table->string('currency', 8)->nullable()->default('NOK');
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('request_hash')->nullable()->index();
            $table->json('payload')->nullable();
            $table->json('result')->nullable();
            $table->decimal('subtotal', 12, 2)->nullable();
            $table->decimal('total', 12, 2)->nullable();
            $table->integer('duration_ms')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
    }

    protected function seedLocalPriceEstimateLogsIfEmpty(): void
    {
        if (! app()->environment('local') || ! Schema::hasTable('price_estimate_logs')) {
            return;
        }

        if (PriceEstimateLog::query()->exists()) {
            return;
        }

        $userId = null;
        if (Schema::hasTable('users')) {
            $userId = DB::table('users')->orderBy('id')->value('id');
        }

        $now = now();
        $rows = [
            [
                'uuid' => (string) Str::uuid(),
                'service_type' => 'delivery',
                'zone' => 'oslo',
                'currency' => 'NOK',
                'user_id' => $userId,
                'request_hash' => Str::random(16),
                'payload' => ['distance_km' => 8.4, 'priority' => 'normal'],
                'result' => ['base' => 120, 'distance_fee' => 82.4, 'final' => 202.4],
                'subtotal' => 202.40,
                'total' => 202.40,
                'duration_ms' => 87,
                'ip_address' => '127.0.0.1',
                'created_at' => $now->copy()->subMinutes(40),
                'updated_at' => $now->copy()->subMinutes(40),
            ],
            [
                'uuid' => (string) Str::uuid(),
                'service_type' => 'roadside_assistance',
                'zone' => 'bergen',
                'currency' => 'NOK',
                'user_id' => null,
                'request_hash' => Str::random(16),
                'payload' => ['incident' => 'battery', 'priority' => 'high'],
                'result' => ['base' => 450, 'surge' => 75, 'final' => 525],
                'subtotal' => 525.00,
                'total' => 525.00,
                'duration_ms' => 213,
                'ip_address' => '127.0.0.1',
                'created_at' => $now->copy()->subMinutes(18),
                'updated_at' => $now->copy()->subMinutes(18),
            ],
            [
                'uuid' => (string) Str::uuid(),
                'service_type' => 'vehicle_transport',
                'zone' => 'trondheim',
                'currency' => 'NOK',
                'user_id' => $userId,
                'request_hash' => Str::random(16),
                'payload' => ['vehicle_type' => 'sedan', 'distance_km' => 42],
                'result' => ['base' => 690, 'distance_fee' => 378, 'final' => 1068],
                'subtotal' => 1068.00,
                'total' => 1068.00,
                'duration_ms' => 501,
                'ip_address' => '127.0.0.1',
                'created_at' => $now->copy()->subMinutes(6),
                'updated_at' => $now->copy()->subMinutes(6),
            ],
        ];

        try {
            foreach ($rows as $row) {
                PriceEstimateLog::query()->create($row);
            }
        } catch (Throwable) {
            // Keep admin page responsive in local mode even if legacy schema differs.
        }
    }
}
