<?php

namespace App\Filament\Resources\CouponResource\Pages;

use App\Filament\Resources\CouponResource;
use App\Models\Coupon;
use Carbon\CarbonInterface;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ListCoupons extends ListRecords
{
    protected static string $resource = CouponResource::class;

    public function mount(): void
    {
        parent::mount();

        $this->seedCouponsIfEmpty();
    }

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function seedCouponsIfEmpty(): void
    {
        if (! app()->environment('local') || ! Schema::hasTable('coupons')) {
            return;
        }

        if (Coupon::query()->exists()) {
            return;
        }

        try {
            $now = now();
            $rows = [
                [
                'code' => 'WELCOME10',
                'name' => 'Welcome discount 10%',
                'type' => 'percent',
                'value' => 10,
                'max_uses' => 1000,
                'used' => 0,
                'minimum_order_amount' => 100,
                'valid_from' => $now->copy()->subDay(),
                'valid_to' => $now->copy()->addMonths(3),
                'applicable_categories' => null,
                'meta' => ['description' => 'Starter coupon for new users'],
                'is_active' => true,
                ],
                [
                'code' => 'DELIVERYFREE',
                'name' => 'Free delivery',
                'type' => 'free_delivery',
                'value' => 0,
                'max_uses' => null,
                'used' => 0,
                'minimum_order_amount' => 150,
                'valid_from' => $now->copy()->subDay(),
                'valid_to' => $now->copy()->addMonths(2),
                'applicable_categories' => ['delivery'],
                'meta' => ['description' => 'Free delivery for eligible orders'],
                'is_active' => true,
                ],
                [
                'code' => 'FIXED50',
                'name' => 'Fixed 50 NOK',
                'type' => 'fixed',
                'value' => 50,
                'max_uses' => 500,
                'used' => 0,
                'minimum_order_amount' => 300,
                'valid_from' => $now->copy()->subDay(),
                'valid_to' => $now->copy()->addMonths(1),
                'applicable_categories' => null,
                'meta' => ['description' => 'Seasonal fixed discount'],
                'is_active' => true,
                ],
                [
                'code' => 'FIRSTORDER',
                'name' => 'First order bonus',
                'type' => 'first_order',
                'value' => 75,
                'max_uses' => null,
                'used' => 0,
                'minimum_order_amount' => 0,
                'valid_from' => $now->copy()->subDay(),
                'valid_to' => $now->copy()->addMonths(6),
                'applicable_categories' => null,
                'meta' => ['description' => 'Bonus for the first completed order'],
                'is_active' => true,
                ],
            ];

            $columns = collect(DB::select("PRAGMA table_info('coupons')"));
            $baseId = (int) (DB::table('coupons')->max('id') ?? 0) + 1;

            foreach ($rows as $index => $row) {
                $prepared = $this->prepareCouponRowForInsert($row, $columns, $baseId + $index, $now);
                DB::table('coupons')->insert($prepared);
            }
        } catch (\Throwable $exception) {
            Log::warning('Unable to seed local coupons', [
                'error' => $exception->getMessage(),
            ]);
        }
    }

    protected function prepareCouponRowForInsert(
        array $row,
        \Illuminate\Support\Collection $columns,
        int $id,
        CarbonInterface $now
    ): array {
        $columnNames = $columns->pluck('name')->all();

        if (in_array('id', $columnNames, true) && ! array_key_exists('id', $row)) {
            $idColumn = $columns->firstWhere('name', 'id');
            $idType = strtolower((string) ($idColumn->type ?? ''));
            $row['id'] = str_contains($idType, 'int') ? $id : (string) Str::uuid();
        }

        foreach ($columns as $column) {
            $name = (string) ($column->name ?? '');
            $notNull = (int) ($column->notnull ?? 0) === 1;
            $hasDefault = $column->dflt_value !== null;

            if ($name === '' || array_key_exists($name, $row) || ! $notNull || $hasDefault) {
                continue;
            }

            $row[$name] = match ($name) {
                'created_at', 'updated_at' => $now,
                'is_active' => 1,
                'used' => 0,
                'max_uses' => 0,
                'minimum_order_amount' => 0,
                'valid_from' => $now->copy()->subDay(),
                'valid_to' => $now->copy()->addMonth(),
                'code' => 'COUPON-'.$id,
                'name' => 'Coupon #'.$id,
                'type' => 'fixed',
                'value' => 0,
                'meta' => json_encode(['seed' => true], JSON_UNESCAPED_UNICODE),
                'applicable_categories' => json_encode([], JSON_UNESCAPED_UNICODE),
                default => $this->defaultValueForRequiredColumn((string) ($column->type ?? ''), $name),
            };
        }

        foreach (['meta', 'applicable_categories'] as $jsonColumn) {
            if (! array_key_exists($jsonColumn, $row)) {
                continue;
            }

            if (is_array($row[$jsonColumn]) || is_object($row[$jsonColumn])) {
                $row[$jsonColumn] = json_encode($row[$jsonColumn], JSON_UNESCAPED_UNICODE);
            }
        }

        return $row;
    }

    protected function defaultValueForRequiredColumn(string $type, string $name): mixed
    {
        $type = strtolower($type);

        if (str_ends_with($name, '_id')) {
            return 1;
        }

        if (str_contains($type, 'int') || str_contains($type, 'real') || str_contains($type, 'numeric') || str_contains($type, 'decimal')) {
            return 0;
        }

        if (str_contains($type, 'bool')) {
            return 0;
        }

        return '';
    }
}
