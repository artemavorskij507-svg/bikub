<?php

namespace App\Filament\Resources\ServiceCategoryResource\Widgets;

use App\Models\ServiceCategory;
use App\Models\ServiceType;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;

class CategoryServicesWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    public ?ServiceCategory $record = null;

    public function getRecord(): ?ServiceCategory
    {
        if ($this->record) {
            return $this->record;
        }

        $owner = $this->getOwner();
        if ($owner instanceof \App\Filament\Resources\ServiceCategoryResource\Pages\EditServiceCategory) {
            return $owner->record;
        }

        return null;
    }

    protected static ?int $sort = 2;

    public function getDefaultTableRecordsPerPageSelectOption(): int
    {
        return 5;
    }

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $record = $this->getRecord();

        if (! $record) {
            return ServiceType::query()->whereRaw('1 = 0');
        }

        return ServiceType::query()
            ->where('service_category_id', $record->id)
            ->orderBy('is_active', 'desc')
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')
                ->label('Name')
                ->searchable()
                ->sortable()
                ->weight('bold'),
            Tables\Columns\TextColumn::make('code')
                ->label('Code')
                ->searchable()
                ->sortable()
                ->copyable(),
            Tables\Columns\BadgeColumn::make('is_active')
                ->label('Status')
                ->formatStateUsing(fn ($state) => $state ? 'Active' : 'Inactive')
                ->colors([
                    'success' => true,
                    'danger' => false,
                ]),
            Tables\Columns\TextColumn::make('sort_order')
                ->label('Sort order')
                ->sortable(),
            Tables\Columns\TextColumn::make('created_at')
                ->label('Created')
                ->dateTime('d.m.Y H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Tables\Actions\Action::make('view')
                ->label('View')
                ->url(fn (ServiceType $record): string => route('filament.resources.service-types.edit', $record))
                ->openUrlInNewTab(),
            Tables\Actions\Action::make('edit')
                ->label('Edit')
                ->url(fn (ServiceType $record): string => route('filament.resources.service-types.edit', $record))
                ->openUrlInNewTab(),
        ];
    }

    protected function getTableHeaderActions(): array
    {
        $record = $this->getRecord();

        if (! $record) {
            return [];
        }

        return [
            Tables\Actions\Action::make('create')
                ->label('Create service')
                ->color('success')
                ->url(fn () => route('filament.resources.service-types.create', ['service_category_id' => $record->id]))
                ->openUrlInNewTab(),
        ];
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'No services in this category yet';
    }

    protected function getTableEmptyStateDescription(): ?string
    {
        $record = $this->getRecord();
        if (! $record) {
            return null;
        }

        return 'Create the first service for category "'.$record->name.'"';
    }

    protected function getTableEmptyStateIcon(): ?string
    {
        return null;
    }

    protected function getTableEmptyStateActions(): array
    {
        $record = $this->getRecord();

        if (! $record) {
            return [];
        }

        return [
            Tables\Actions\Action::make('create')
                ->label('Create service')
                ->color('success')
                ->url(fn () => route('filament.resources.service-types.create', ['service_category_id' => $record->id]))
                ->openUrlInNewTab(),
        ];
    }

    protected function getTableHeading(): string
    {
        return 'Services in this category';
    }

    protected function getTableDescription(): ?string
    {
        $record = $this->getRecord();

        if (! $record) {
            return null;
        }

        $count = $record->serviceTypes()->count();

        return "Showing up to {$this->getDefaultTableRecordsPerPageSelectOption()} of {$count} services";
    }
}
