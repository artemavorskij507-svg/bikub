<?php

namespace App\Filament\Resources;

use App\Domain\Dispatch\Actions\NormalizeDispatchRuleKeyAction;
use App\Domain\Dispatch\Actions\ValidateDispatchRuleValueAction;
use App\Domain\Dispatch\Models\DispatchRuleSet;
use App\Domain\Ops\Actions\RecordDispatchConfigAuditAction;
use App\Filament\Resources\DispatchRuleSetResource\Pages;
use App\Support\Dispatch\DispatchRuleCatalog;
use App\Support\Dispatch\DispatchRuleFormSchema;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class DispatchRuleSetResource extends Resource
{
    protected static ?string $model = DispatchRuleSet::class;
    protected static ?string $navigationIcon = 'heroicon-o-adjustments';
    protected static ?string $navigationGroup = 'Операции';
    protected static ?string $navigationLabel = 'Dispatch Rule Sets';
    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('service_domain')->required()->options([
                'delivery' => 'Delivery',
                'handyman' => 'Handyman',
                'moving' => 'Moving',
                'roadside' => 'Roadside',
                'social_care' => 'Social Care',
            ]),
            Forms\Components\TextInput::make('job_kind')->maxLength(64),
            Forms\Components\Select::make('rule_key')->label('Rule key')->searchable()->required()->options(DispatchRuleCatalog::options())->reactive(),
            DispatchRuleFormSchema::valueField(),
            Forms\Components\Placeholder::make('rule_default_value')
                ->label('Default value')
                ->content(function (callable $get, ?DispatchRuleSet $record): string {
                    $domain = (string) ($get('service_domain') ?: ($record?->service_domain ?: 'delivery'));
                    $ruleKey = (string) ($get('rule_key') ?: ($record?->rule_key ?: ''));
                    if ($ruleKey === '') {
                        return '-';
                    }

                    return self::formatValue(DispatchRuleCatalog::defaultValueFor($domain, $ruleKey));
                }),
            Forms\Components\Placeholder::make('rule_override_value')
                ->label('Current override')
                ->content(function (callable $get, ?DispatchRuleSet $record): string {
                    $inputValue = $get('rule_value');
                    if ($inputValue !== null && $inputValue !== '') {
                        return self::formatValue($inputValue);
                    }

                    $stored = self::extractRuleValue($record);

                    return self::formatValue($stored);
                }),
            Forms\Components\Placeholder::make('rule_delta')
                ->label('Delta vs default')
                ->content(function (callable $get, ?DispatchRuleSet $record): string {
                    $insight = self::computeRuleInsightFromForm($get, $record);

                    return $insight['delta_label'];
                }),
            Forms\Components\Placeholder::make('rule_impact')
                ->label('Impact')
                ->content(function (callable $get, ?DispatchRuleSet $record): string {
                    $insight = self::computeRuleInsightFromForm($get, $record);

                    return $insight['impact_label'];
                }),
            Forms\Components\Toggle::make('is_active')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\BadgeColumn::make('service_domain'),
            Tables\Columns\TextColumn::make('job_kind')->default('*'),
            Tables\Columns\TextColumn::make('rule_key')->searchable()->wrap(),
            Tables\Columns\TextColumn::make('rule_value_display')
                ->label('Rule value')
                ->getStateUsing(fn (DispatchRuleSet $record) => self::formatValue(self::extractRuleValue($record))),
            Tables\Columns\BadgeColumn::make('impact')
                ->label('Impact')
                ->getStateUsing(function (DispatchRuleSet $record): string {
                    $insight = self::computeRuleInsightFromRecord($record);

                    return $insight['impact_label'];
                })
                ->colors([
                    'secondary' => 'Normal',
                    'warning' => 'Aggressive override',
                    'danger' => 'High impact',
                ]),
            Tables\Columns\IconColumn::make('is_active')->boolean(),
            Tables\Columns\TextColumn::make('updated_at')->since(),
        ])->filters([
            Tables\Filters\SelectFilter::make('service_domain')->options([
                'delivery' => 'Delivery', 'handyman' => 'Handyman', 'moving' => 'Moving', 'roadside' => 'Roadside', 'social_care' => 'Social Care',
            ]),
            Tables\Filters\TernaryFilter::make('is_active'),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ])->bulkActions([
            Tables\Actions\DeleteBulkAction::make(),
        ])->defaultSort('updated_at', 'desc');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::hasDispatchRulesTable();
    }

    public static function canViewAny(): bool
    {
        if (! self::hasDispatchRulesTable()) {
            return false;
        }

        return parent::canViewAny();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function mutateBeforeSave(array $data): array
    {
        $ruleKey = app(NormalizeDispatchRuleKeyAction::class)->execute((string) ($data['rule_key'] ?? ''));
        $value = app(ValidateDispatchRuleValueAction::class)->execute($ruleKey, $data['rule_value'] ?? null);
        $data['rule_key'] = $ruleKey;
        if (self::hasRuleValueJsonColumn()) {
            $data['rule_value_json'] = ['value' => $value];
        } elseif (self::hasRuleValueColumn()) {
            $data['rule_value'] = (string) $value;
        } else {
            // Fallback for legacy drift: keep primary JSON payload.
            $data['rule_value_json'] = ['value' => $value];
        }
        unset($data['rule_value']);

        return $data;
    }

    public static function audit(string $action, ?DispatchRuleSet $record, array $before = []): void
    {
        $after = $record?->toArray() ?? [];
        $domain = (string) ($after['service_domain'] ?? ($before['service_domain'] ?? 'delivery'));
        $ruleKey = (string) ($after['rule_key'] ?? ($before['rule_key'] ?? ''));
        $defaultValue = $ruleKey !== '' ? DispatchRuleCatalog::defaultValueFor($domain, $ruleKey) : null;
        $overrideValue = $after !== [] ? self::extractRuleValueArray($after) : self::extractRuleValueArray($before);
        $deltaPercent = DispatchRuleCatalog::deltaPercent($defaultValue, $overrideValue);
        $impact = $ruleKey !== '' ? DispatchRuleCatalog::impactLevel($domain, $ruleKey, $overrideValue) : 'normal';

        app(RecordDispatchConfigAuditAction::class)->execute(
            auth()->id(),
            $action,
            'dispatch_rule_set',
            $record?->id,
            $before,
            $after,
            [
                'default_value' => $defaultValue,
                'override_value' => $overrideValue,
                'delta_percent' => $deltaPercent,
                'impact_level' => $impact,
            ]
        );
    }

    public static function reportValidationError(\Throwable $e): never
    {
        Notification::make()->danger()->title('Invalid rule value')->body($e->getMessage())->send();
        throw ValidationException::withMessages(['rule_value' => $e->getMessage()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDispatchRuleSets::route('/'),
            'create' => Pages\CreateDispatchRuleSet::route('/create'),
            'edit' => Pages\EditDispatchRuleSet::route('/{record}/edit'),
        ];
    }

    private static function computeRuleInsightFromForm(callable $get, ?DispatchRuleSet $record): array
    {
        $domain = (string) ($get('service_domain') ?: ($record?->service_domain ?: 'delivery'));
        $ruleKey = (string) ($get('rule_key') ?: ($record?->rule_key ?: ''));
        $rawOverride = $get('rule_value');
        $override = ($rawOverride !== null && $rawOverride !== '') ? $rawOverride : self::extractRuleValue($record);

        return self::computeRuleInsight($domain, $ruleKey, $override);
    }

    private static function computeRuleInsightFromRecord(DispatchRuleSet $record): array
    {
        return self::computeRuleInsight(
            (string) ($record->service_domain ?: 'delivery'),
            (string) ($record->rule_key ?: ''),
            self::extractRuleValue($record)
        );
    }

    private static function computeRuleInsight(string $domain, string $ruleKey, mixed $override): array
    {
        if ($ruleKey === '') {
            return [
                'impact' => 'normal',
                'impact_label' => 'Normal',
                'delta_percent' => null,
                'delta_label' => '-',
            ];
        }

        $default = DispatchRuleCatalog::defaultValueFor($domain, $ruleKey);
        $impact = DispatchRuleCatalog::impactLevel($domain, $ruleKey, $override);
        $delta = DispatchRuleCatalog::deltaPercent($default, $override);

        return [
            'impact' => $impact,
            'impact_label' => self::impactLabel($impact),
            'delta_percent' => $delta,
            'delta_label' => $delta === null ? '-' : "{$delta}%",
        ];
    }

    private static function impactLabel(string $impact): string
    {
        return match ($impact) {
            'high_impact' => 'High impact',
            'aggressive_override' => 'Aggressive override',
            default => 'Normal',
        };
    }

    private static function extractRuleValue(?DispatchRuleSet $record): mixed
    {
        if (! $record) {
            return null;
        }

        return self::extractRuleValueArray($record->toArray());
    }

    private static function extractRuleValueArray(array $record): mixed
    {
        $jsonState = $record['rule_value_json'] ?? null;
        if (is_array($jsonState) && array_key_exists('value', $jsonState)) {
            return $jsonState['value'];
        }

        if (array_key_exists('rule_value', $record)) {
            return $record['rule_value'];
        }

        return $jsonState;
    }

    private static function formatValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return (string) $value;
    }

    private static function hasDispatchRulesTable(): bool
    {
        return Schema::hasTable('dispatch_rule_sets');
    }

    private static function hasRuleValueJsonColumn(): bool
    {
        return self::hasDispatchRulesTable() && Schema::hasColumn('dispatch_rule_sets', 'rule_value_json');
    }

    private static function hasRuleValueColumn(): bool
    {
        return self::hasDispatchRulesTable() && Schema::hasColumn('dispatch_rule_sets', 'rule_value');
    }
}
