<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductStorePrice;
use App\Models\ServiceCategory;
use App\Models\ServiceType;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class CatalogAudit extends Command
{
    protected $signature = 'app:catalog-audit';

    protected $description = 'Analyze catalog tables, detect duplicates and missing attributes, and export a CSV report.';

    public function handle(): int
    {
        $reportRows = array_merge(
            $this->analyzeProducts(),
            $this->analyzeServiceCategories(),
            $this->analyzeServiceTypes(),
            $this->analyzeProductStorePrices(),
        );

        if (empty($reportRows)) {
            $this->warn('No catalog data found to analyze.');

            return Command::SUCCESS;
        }

        $dir = storage_path('reports');
        if (! File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $timestamp = Carbon::now()->format('Ymd_His');
        $path = $dir.DIRECTORY_SEPARATOR."catalog_audit_{$timestamp}.csv";

        $handle = fopen($path, 'w');
        fputcsv($handle, ['id', 'name', 'type', 'category', 'duplicates_count', 'missing_attrs', 'suggested_action']);
        foreach ($reportRows as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);

        $this->info("Catalog audit report generated: {$path}");

        return Command::SUCCESS;
    }

    /**
     * @return array<int, array<int, string|int>>
     */
    protected function analyzeProducts(): array
    {
        $rows = [];
        $columns = Schema::getColumnListing('products');
        $requiredAttrs = ['sku', 'weight_kg', 'volume_m3', 'dimensions', 'unit'];
        $duplicates = $this->duplicateCounts('products', 'name');

        Product::query()->select(['id', 'name', 'sku'])->chunkById(250, function ($products) use (&$rows, $columns, $requiredAttrs, $duplicates) {
            foreach ($products as $product) {
                $canonical = $this->canonicalName($product->name, $product->id);
                $duplicateCount = $this->duplicateCountFor($canonical, $duplicates);
                $missing = $this->detectMissingAttributes($product, $requiredAttrs, $columns);
                $rows[] = [
                    $product->id,
                    $product->name,
                    'product',
                    '—',
                    $duplicateCount,
                    json_encode($missing, JSON_UNESCAPED_UNICODE),
                    $this->suggestedAction($duplicateCount, $missing),
                ];
            }
        });

        return $rows;
    }

    protected function analyzeServiceCategories(): array
    {
        $rows = [];
        $duplicates = $this->duplicateCounts('service_categories', 'name');

        ServiceCategory::query()->select(['id', 'name', 'code'])->chunkById(250, function ($categories) use (&$rows, $duplicates) {
            foreach ($categories as $category) {
                $canonical = $this->canonicalName($category->name, $category->id);
                $duplicateCount = $this->duplicateCountFor($canonical, $duplicates);
                $rows[] = [
                    $category->id,
                    $category->name,
                    'service_category',
                    $category->code ?? '—',
                    $duplicateCount,
                    json_encode([], JSON_UNESCAPED_UNICODE),
                    $this->suggestedAction($duplicateCount, []),
                ];
            }
        });

        return $rows;
    }

    protected function analyzeServiceTypes(): array
    {
        $rows = [];
        $duplicates = $this->duplicateCounts('service_types', 'name');

        ServiceType::query()
            ->select(['id', 'name', 'code', 'service_category_id'])
            ->with('serviceCategory:id,name')
            ->chunkById(250, function ($types) use (&$rows, $duplicates) {
                foreach ($types as $type) {
                    $canonical = $this->canonicalName($type->name, $type->id);
                    $duplicateCount = $this->duplicateCountFor($canonical, $duplicates);
                    $categoryName = $type->serviceCategory->name ?? '—';
                    $rows[] = [
                        $type->id,
                        $type->name,
                        'service_type',
                        $categoryName,
                        $duplicateCount,
                        json_encode([], JSON_UNESCAPED_UNICODE),
                        $this->suggestedAction($duplicateCount, []),
                    ];
                }
            });

        return $rows;
    }

    protected function analyzeProductStorePrices(): array
    {
        $rows = [];
        $productDuplicates = $this->duplicateCounts('products', 'name');

        ProductStorePrice::query()
            ->with([
                'product:id,name',
                'store:id,name',
            ])
            ->chunkById(250, function ($prices) use (&$rows, $productDuplicates) {
                foreach ($prices as $price) {
                    $productName = $price->product->name ?? 'Unknown product';
                    $storeName = $price->store->name ?? 'Unknown store';
                    $canonical = $this->canonicalName($productName, $price->product->id ?? $price->id);
                    $duplicateCount = $this->duplicateCountFor($canonical, $productDuplicates);
                    $missing = [];
                    if ($price->price === null) {
                        $missing[] = ['attribute' => 'price', 'reason' => 'value_missing'];
                    }

                    $rows[] = [
                        $price->id,
                        "{$productName} @ {$storeName}",
                        'product_store_price',
                        $storeName,
                        $duplicateCount,
                        json_encode($missing, JSON_UNESCAPED_UNICODE),
                        $this->suggestedAction($duplicateCount, $missing),
                    ];
                }
            });

        return $rows;
    }

    /**
     * @param  array<int, string>  $requiredAttrs
     * @param  array<int, string>  $columns
     */
    protected function detectMissingAttributes(object $model, array $requiredAttrs, array $columns): array
    {
        $missing = [];
        foreach ($requiredAttrs as $attr) {
            if (! in_array($attr, $columns, true)) {
                $missing[] = ['attribute' => $attr, 'reason' => 'column_missing'];

                continue;
            }

            $value = $model->{$attr} ?? null;
            if ($value === null || $value === '') {
                $missing[] = ['attribute' => $attr, 'reason' => 'value_missing'];
            }
        }

        return $missing;
    }

    protected function duplicateCounts(string $table, string $column): array
    {
        return DB::table($table)
            ->selectRaw('LOWER(TRIM(COALESCE('.$column.", ''))) as canonical_name, COUNT(*) as aggregate_count")
            ->groupBy('canonical_name')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('aggregate_count', 'canonical_name')
            ->toArray();
    }

    protected function duplicateCountFor(string $canonical, array $duplicates): int
    {
        return max(0, ($duplicates[$canonical] ?? 1) - 1);
    }

    protected function canonicalName(?string $value, ?int $fallbackId = null): string
    {
        $canonical = mb_strtolower(trim((string) $value));

        if ($canonical === '') {
            $canonical = $fallbackId ? "item-{$fallbackId}" : uniqid('item-', false);
        }

        return $canonical;
    }

    /**
     * @param  array<int, array<string, string>>  $missing
     */
    protected function suggestedAction(int $duplicateCount, array $missing): string
    {
        $hasDuplicates = $duplicateCount > 0;
        $hasMissing = ! empty($missing);

        return match (true) {
            $hasDuplicates && $hasMissing => 'review_duplicates_and_enrich',
            $hasDuplicates => 'review_duplicates',
            $hasMissing => 'enrich_attributes',
            default => 'ok',
        };
    }
}
