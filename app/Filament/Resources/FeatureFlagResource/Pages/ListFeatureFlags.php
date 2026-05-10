<?php

namespace App\Filament\Resources\FeatureFlagResource\Pages;

use App\Filament\Resources\FeatureFlagResource;
use App\Models\FeatureFlag;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\HtmlString;

class ListFeatureFlags extends ListRecords
{
    protected static string $resource = FeatureFlagResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->seedLocalDemoFeatureFlagsIfEmpty();
    }

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('sync_from_config')
                ->label('Синхронизировать с конфигом')
                ->icon('heroicon-o-refresh')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Синхронизация с config/feature_flags.php')
                ->modalContent(new HtmlString('Создать записи для всех флагов из конфига, которых еще нет в БД.'))
                ->action(function () {
                    $configFlags = config('feature_flags', []);
                    $created = 0;

                    foreach ($configFlags as $key => $value) {
                        if (is_array($value) && isset($value['enabled'])) {
                            $enabled = (bool) ($value['enabled'] ?? false);
                        } elseif (is_bool($value)) {
                            $enabled = $value;
                        } else {
                            continue;
                        }

                        if (! FeatureFlag::where('key', $key)->exists()) {
                            FeatureFlag::create([
                                'key' => $key,
                                'enabled' => $enabled,
                                'settings' => is_array($value) ? $value : null,
                                'reason' => 'Imported from config',
                            ]);
                            $created++;
                        }
                    }

                    \Filament\Notifications\Notification::make()
                        ->title('Синхронизация завершена')
                        ->body("Создано {$created} новых флагов")
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function seedLocalDemoFeatureFlagsIfEmpty(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        if (FeatureFlag::query()->exists()) {
            return;
        }

        $flags = [
            ['virtual_office_2d', true],
            ['assistant_chat', true],
            ['strict_payment_gate', false],
            ['schedule_slot_optimizer', true],
            ['notifications_push', true],
        ];

        foreach ($flags as [$key, $enabled]) {
            FeatureFlag::query()->create([
                'key' => $key,
                'enabled' => $enabled,
                'settings' => [
                    'source' => 'local_demo_seed',
                ],
                'reason' => 'Auto-generated local demo feature flag',
            ]);
        }
    }
}
