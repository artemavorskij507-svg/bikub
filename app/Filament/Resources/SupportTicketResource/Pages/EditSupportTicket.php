<?php

namespace App\Filament\Resources\SupportTicketResource\Pages;

use App\Filament\Resources\SupportTicketResource;
use App\Filament\Resources\SupportTicketResource\Widgets\TicketStatsWidget;
use Filament\Notifications\Notification;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditSupportTicket extends EditRecord
{
    protected static string $resource = SupportTicketResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            TicketStatsWidget::class,
        ];
    }

    protected function getActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('Просмотр')
                ->color('info'),
            Actions\Action::make('markResolved')
                ->label('Закрыть тикет')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Закрыть тикет?')
                ->modalSubheading('Тикет будет помечен как решённый. Это действие можно отменить.')
                ->visible(fn () => ! $this->record->isResolved())
                ->action(function () {
                    $this->record->update([
                        'status' => 'resolved',
                        'resolved_at' => now(),
                        'resolved_by' => auth()->id(),
                    ]);

                    Notification::make()
                        ->title('Тикет закрыт')
                        ->body("Тикет {$this->record->number} успешно закрыт.")
                        ->success()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('edit', ['record' => $this->record]));
                }),
            Actions\Action::make('reopen')
                ->label('Открыть заново')
                ->icon('heroicon-o-refresh')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Открыть тикет заново?')
                ->modalSubheading('Тикет будет возвращён в статус "В работе".')
                ->visible(fn () => $this->record->isResolved())
                ->action(function () {
                    $this->record->update([
                        'status' => 'in_progress',
                        'resolved_at' => null,
                        'resolved_by' => null,
                    ]);

                    Notification::make()
                        ->title('Тикет открыт заново')
                        ->body("Тикет {$this->record->number} возвращён в работу.")
                        ->success()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('edit', ['record' => $this->record]));
                }),
            Actions\Action::make('setUrgent')
                ->label('Сделать срочным')
                ->icon('heroicon-o-flag')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->priority !== 'urgent')
                ->action(function () {
                    $this->record->update(['priority' => 'urgent']);

                    Notification::make()
                        ->title('Приоритет изменён')
                        ->body('Тикет помечен как срочный.')
                        ->warning()
                        ->send();

                    $this->redirect($this->getResource()::getUrl('edit', ['record' => $this->record]));
                }),
            Actions\Action::make('duplicate')
                ->label('Дублировать')
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->requiresConfirmation()
                ->modalSubheading('Создать копию тикета с тем же содержимым?')
                ->action(function () {
                    $newTicket = $this->record->replicate();
                    $newTicket->status = 'open';
                    $newTicket->resolved_at = null;
                    $newTicket->resolved_by = null;
                    $newTicket->save();

                    Notification::make()
                        ->title('Тикет продублирован')
                        ->body("Создан новый тикет: {$newTicket->number}")
                        ->success()
                        ->actions([
                            \Filament\Notifications\Actions\Action::make('view')
                                ->label('Открыть')
                                ->url($this->getResource()::getUrl('edit', ['record' => $newTicket])),
                        ])
                        ->send();
                }),
        ];
    }

    public function getHeading(): string
    {
        return $this->record->number ?? 'Редактирование тикета';
    }

    public function getSubheading(): ?string
    {
        if (! $this->record) {
            return null;
        }

        $statusLabel = match ($this->record->status) {
            'open' => 'Открыт',
            'in_progress' => 'В работе',
            'resolved' => 'Решён',
            'closed' => 'Закрыт',
            default => $this->record->status,
        };

        $priorityLabel = match ($this->record->priority) {
            'urgent' => 'Срочный',
            'high' => 'Высокий',
            'normal' => 'Обычный',
            'low' => 'Низкий',
            default => $this->record->priority,
        };

        $subject = Str::limit($this->record->subject ?? 'Без темы', 60);

        return "{$subject} • {$statusLabel} • {$priorityLabel}";
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Если статус меняется на resolved и resolved_at пустой
        if (($data['status'] === 'resolved' || $data['status'] === 'closed') && ! $this->record->resolved_at) {
            $data['resolved_at'] = now();
            $data['resolved_by'] = auth()->id();
        }

        // Если статус меняется обратно на открытый, очищаем resolved_at
        if (in_array($data['status'], ['open', 'in_progress'], true) && $this->record->resolved_at) {
            $data['resolved_at'] = null;
            $data['resolved_by'] = null;
        }

        return $data;
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Тикет обновлён')
            ->body("Изменения в тикете {$this->record->number} успешно сохранены.")
            ->success();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
