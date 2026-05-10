<?php

namespace App\Filament\Widgets\Ops;

use App\Domain\Ops\Queries\OperationExceptionsTableQuery;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class OpenExceptionsWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 2;

    protected function getTableQuery(): Builder
    {
        return app(OperationExceptionsTableQuery::class)
            ->builder(['status' => 'open'])
            ->whereIn('status', ['open', 'acknowledged', 'investigating', 'mitigated'])
            ->limit(20);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->heading('Open Exceptions')
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID'),
                Tables\Columns\TextColumn::make('type')->label('Type'),
                Tables\Columns\TextColumn::make('severity')->label('Severity'),
                Tables\Columns\TextColumn::make('service_job_id')->label('Job'),
                Tables\Columns\TextColumn::make('detected_at')->label('Detected')->dateTime('H:i'),
            ])
            ->paginated([10]);
    }
}

