<?php

namespace App\Support\Dispatch;

use Filament\Forms;

class DispatchRuleFormSchema
{
    public static function valueField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('rule_value')
            ->label('Rule value')
            ->required()
            ->afterStateHydrated(function (Forms\Components\TextInput $component, $state, $record): void {
                if ($state !== null || ! $record) {
                    return;
                }

                $value = is_array($record->rule_value_json)
                    ? ($record->rule_value_json['value'] ?? null)
                    : null;

                if ($value === null && isset($record->rule_value)) {
                    $value = $record->rule_value;
                }

                if ($value !== null) {
                    $component->state((string) $value);
                }
            })
            ->helperText('Typed value. Saved as JSON in rule_value_json.value.');
    }
}
