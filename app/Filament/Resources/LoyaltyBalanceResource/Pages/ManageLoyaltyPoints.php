<?php

namespace App\Filament\Resources\LoyaltyBalanceResource\Pages;

use App\Filament\Resources\LoyaltyBalanceResource;
use App\Models\User;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Actions;
use Filament\Resources\Pages\Page;

class ManageLoyaltyPoints extends Page
{
    protected static string $resource = LoyaltyBalanceResource::class;

    protected static string $view = 'filament.resources.loyalty-balance-resource.pages.manage-loyalty-points';

    protected static ?string $title = 'Керування балами лояльності';

    protected static ?string $navigationLabel = 'Керування балами';

    protected static ?int $navigationSort = 3;

    public function getTitle(): string
    {
        return 'Керування балами лояльності';
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Card::make()
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->label('Користувач')
                        ->relationship('user', 'email')
                        ->required()
                        ->searchable()
                        ->preload(),

                    Forms\Components\Radio::make('action')
                        ->label('Дія')
                        ->options([
                            'add' => 'Додати бали',
                            'remove' => 'Видалити бали',
                        ])
                        ->default('add')
                        ->required()
                        ->inline(),

                    Forms\Components\TextInput::make('amount')
                        ->label('Кількість балів')
                        ->numeric()
                        ->minValue(1)
                        ->required(),

                    Forms\Components\Textarea::make('reason')
                        ->label('Причина')
                        ->required()
                        ->maxLength(500)
                        ->rows(3)
                        ->placeholder('Наприклад: Відшкодування за помилку, промотивні бали, бонус нового користувача'),
                ])
                ->columns(1),
        ];
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        $user = User::findOrFail($data['user_id']);
        $balance = $user->getOrCreateLoyaltyBalance();

        $amount = (int) $data['amount'];
        $type = $data['action'] === 'add' ? 'manual_add' : 'manual_remove';
        $points = $data['action'] === 'add' ? $amount : -$amount;

        $transaction = $balance->transactions()->create([
            'user_id' => $user->id,
            'type' => $type,
            'points_amount' => $points,
            'description' => $data['reason'],
        ]);

        // Update points
        $balance->update([
            'points' => max(0, $balance->points + $points),
            'lifetime_points' => $balance->lifetime_points + abs($points),
        ]);

        Notification::make()
            ->title('Успіх!')
            ->body('Бали успішно '.($data['action'] === 'add' ? 'додані' : 'видалені').' користувачу '.$user->email)
            ->success()
            ->send();

        $this->form->fill();
    }

    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('submit')
                ->label('Застосувати')
                ->submit('submit')
                ->color('success'),
        ];
    }
}
