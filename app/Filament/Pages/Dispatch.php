<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Http;

class Dispatch extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationLabel = 'Диспетчерская';

    protected static ?string $navigationGroup = 'System & Operations';

    protected static ?int $navigationSort = 102;

    protected static string $view = 'filament.pages.dispatch';

    public ?array $filters = [];

    public ?array $dispatchState = [];

    public bool $winterProtocolEnabled = false;

    public float $etaMultiplier = 1.2;

    public int $priorityBoost = 1;

    public function mount(): void
    {
        // Initialize filters array with proper structure
        if (empty($this->filters)) {
            $this->filters = [
                'module' => null,
                'zone' => null,
                'slot' => null,
                'status' => null,
            ];
        }
        $this->loadDispatchState();
        $this->loadWinterProtocol();
    }

    public function updatedFilters($value, $key): void
    {
        // Auto-reload when filters change
        $this->loadDispatchState();
    }

    public function loadDispatchState(): void
    {
        try {
            $response = Http::timeout(5)->get(url('/api/v1/dispatch/state'), $this->filters);
            if ($response->successful()) {
                $this->dispatchState = $response->json('data') ?? [];
            } else {
                $this->dispatchState = ['status' => 'API unavailable', 'orders' => []];
            }
        } catch (\Exception $e) {
            $this->dispatchState = ['status' => 'offline', 'message' => 'API endpoint not configured'];
            \Log::debug('Dispatch state API not available: '.$e->getMessage());
        }
    }

    public function loadWinterProtocol(): void
    {
        try {
            $response = Http::timeout(5)->get(url('/api/v1/dispatch/winter-protocol'));
            if ($response->successful()) {
                $data = $response->json('data');
                $this->winterProtocolEnabled = $data['enabled'] ?? false;
                $this->etaMultiplier = $data['eta_multiplier'] ?? 1.2;
                $this->priorityBoost = $data['priority_boost'] ?? 1;
            }
        } catch (\Exception $e) {
            \Log::debug('Winter protocol API not available: '.$e->getMessage());
        }
    }

    public function updateFilters(): void
    {
        $this->loadDispatchState();
    }

    public function toggleWinterProtocol(): void
    {
        try {
            $response = Http::post(url('/api/v1/dispatch/winter-protocol'), [
                'enabled' => $this->winterProtocolEnabled,
                'eta_multiplier' => $this->etaMultiplier,
                'priority_boost' => $this->priorityBoost,
            ]);

            if ($response->successful()) {
                $this->loadDispatchState(); // Reload with new settings
            }
        } catch (\Exception $e) {
            // Handle error
        }
    }

    public function updateOrderStatus(string $orderId, string $status): void
    {
        try {
            $response = Http::patch(url("/api/v1/dispatch/orders/{$orderId}/status"), [
                'status' => $status,
            ]);

            if ($response->successful()) {
                $this->loadDispatchState();
            }
        } catch (\Exception $e) {
            // Handle error
        }
    }

    public function assignTask(string $taskId, string $assigneeId): void
    {
        try {
            $response = Http::patch(url("/api/v1/dispatch/tasks/{$taskId}/assign"), [
                'assignee_id' => $assigneeId,
            ]);

            if ($response->successful()) {
                $this->loadDispatchState();
            }
        } catch (\Exception $e) {
            // Handle error
        }
    }

    protected function getActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Обновить')
                ->icon('heroicon-o-refresh')
                ->action(fn () => $this->loadDispatchState()),

            Action::make('winter_protocol')
                ->label('Зимний протокол')
                ->icon('heroicon-o-sparkles')
                ->form([
                    Toggle::make('enabled')
                        ->label('Включен')
                        ->default($this->winterProtocolEnabled),
                    TextInput::make('eta_multiplier')
                        ->label('Коэффициент ETA')
                        ->numeric()
                        ->default($this->etaMultiplier)
                        ->minValue(1)
                        ->maxValue(2)
                        ->step(0.1),
                    TextInput::make('priority_boost')
                        ->label('Повышение приоритета')
                        ->numeric()
                        ->default($this->priorityBoost)
                        ->minValue(0)
                        ->maxValue(3),
                ])
                ->action(function (array $data) {
                    $this->winterProtocolEnabled = $data['enabled'];
                    $this->etaMultiplier = $data['eta_multiplier'];
                    $this->priorityBoost = $data['priority_boost'];
                    $this->toggleWinterProtocol();
                }),
        ];
    }

    public function getTitle(): string
    {
        return 'Диспетчерская - Real-time управление';
    }
}
