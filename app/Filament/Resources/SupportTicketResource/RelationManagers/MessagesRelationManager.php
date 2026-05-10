<?php

namespace App\Filament\Resources\SupportTicketResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Illuminate\Contracts\View\View;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    protected static ?string $recordTitleAttribute = 'message';

    protected static ?string $title = 'Чат';

    protected int $poll = 5; // Обновление каждые 5 секунд

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Textarea::make('message')
                    ->label('Сообщение')
                    ->required()
                    ->maxLength(5000)
                    ->rows(4)
                    ->placeholder('Введите ваше сообщение...'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([])
            ->filters([])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }

    public function render(): View
    {
        return view('filament.resources.support-ticket-resource.relation-managers.chat-wrapper', [
            'ticket' => $this->ownerRecord,
        ]);
    }
}
