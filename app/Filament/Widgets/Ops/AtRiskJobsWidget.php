<?php

namespace App\Filament\Widgets\Ops;

use App\Domain\Ops\Queries\ServiceJobsTableQuery;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class AtRiskJobsWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 2;

    protected function getTableQuery(): Builder
    {
        return app(ServiceJobsTableQuery::class)->builder(['at_risk_only' => true])->limit(20);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->heading('At Risk Jobs')
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('Job')->sortable(),
                Tables\Columns\TextColumn::make('service_domain')->label('Domain'),
                Tables\Columns\TextColumn::make('status')->label('Status'),
                Tables\Columns\TextColumn::make('updated_at')->label('Updated')->dateTime('H:i'),
            ])
            ->paginated([10]);
    }
}

