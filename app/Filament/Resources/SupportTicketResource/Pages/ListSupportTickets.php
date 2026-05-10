<?php

namespace App\Filament\Resources\SupportTicketResource\Pages;

use App\Filament\Resources\SupportTicketResource;
use App\Filament\Resources\SupportTicketResource\Widgets\SupportTicketsOverviewWidget;
use App\Models\SupportTicket;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListSupportTickets extends ListRecords
{
    protected static string $resource = SupportTicketResource::class;

    protected function getActions(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SupportTicketsOverviewWidget::class,
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(SupportTicket::count()),
            'open' => Tab::make('Open')
                ->badge(SupportTicket::where('status', 'open')->count())
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'open')),
            'in_progress' => Tab::make('In Progress')
                ->badge(SupportTicket::where('status', 'in_progress')->count())
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'in_progress')),
            'resolved' => Tab::make('Resolved')
                ->badge(SupportTicket::where('status', 'resolved')->count())
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'resolved')),
            'urgent' => Tab::make('Urgent')
                ->badge(SupportTicket::where('priority', 'urgent')->whereNotIn('status', ['resolved', 'closed'])->count())
                ->modifyQueryUsing(fn ($query) => $query->where('priority', 'urgent')),
        ];
    }
}
