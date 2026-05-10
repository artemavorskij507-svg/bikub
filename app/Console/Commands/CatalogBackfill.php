<?php

namespace App\Console\Commands;

use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class CatalogBackfill extends Command
{
    protected $signature = 'app:catalog-backfill';

    protected $description = 'Normalize products catalog attributes using heuristics and imported reference files.';

    /**
     * File cache [{path: string, content: string}]
     *
     * @var array<int, array{path: string, content: string}>
     */
    protected array $dataFiles = [];

    /**
     * Heuristic rules for weight/volume/unit inference.
     *
     * @var array<int, array{pattern: string, weight: float, volume: float, unit?: string}>
     */
    protected array $attributeRules = [
        ['pattern' => 'диван', 'weight' => 65, 'volume' => 2.2, 'unit' => 'pcs'],
        ['pattern' => 'кровать', 'weight' => 70, 'volume' => 2.6, 'unit' => 'pcs'],
        ['pattern' => 'стирал', 'weight' => 62, 'volume' => 1.0, 'unit' => 'pcs'],
        ['pattern' => 'холод', 'weight' => 78, 'volume' => 1.3, 'unit' => 'pcs'],
        ['pattern' => 'шкаф', 'weight' => 85, 'volume' => 2.9, 'unit' => 'pcs'],
        ['pattern' => 'стол', 'weight' => 32, 'volume' => 0.9, 'unit' => 'pcs'],
        ['pattern' => 'стул', 'weight' => 7, 'volume' => 0.2, 'unit' => 'pcs'],
        ['pattern' => 'телевиз', 'weight' => 18, 'volume' => 0.3, 'unit' => 'pcs'],
        ['pattern' => 'микровол', 'weight' => 12, 'volume' => 0.15, 'unit' => 'pcs'],
        ['pattern' => 'пылесос', 'weight' => 6, 'volume' => 0.12, 'unit' => 'pcs'],
    ];

    public function handle(): int
    {
        if (! $this->ensureColumnsExist()) {
            $this->error('Required columns are missing. Run the migrations first.');

            return Command::FAILURE;
        }

        $this->dataFiles = $this->loadDataFiles();
        $timestamp = Carbon::now()->format('Ymd_His');
        $logPath = storage_path('reports/catalog_backfill_'.$timestamp.'.log');
        if (! File::exists(dirname($logPath))) {
            File::makeDirectory(dirname($logPath), 0755, true);
        }
        $logHandle = fopen($logPath, 'w');

        $total = Product::count();
        $updated = 0;

        Product::query()->select(['id', 'name', 'sku', 'canonical_name', 'weight_kg', 'volume_m3', 'unit', 'source_file'])->chunkById(200, function ($products) use (&$updated, $logHandle) {
            foreach ($products as $product) {
                $changes = $this->backfillProduct($product);

                if (! empty($changes)) {
                    $product->save();
                    $updated++;
                    $this->log($logHandle, sprintf(
                        'Product #%d "%s": updated [%s]',
                        $product->id,
                        $product->name,
                        implode(', ', $changes)
                    ));
                }
            }
        });

        fclose($logHandle);

        $this->info("Backfill complete. Updated {$updated} of {$total} products.");
        $this->info("Log written to: {$logPath}");

        return Command::SUCCESS;
    }

    protected function ensureColumnsExist(): bool
    {
        $productColumns = ['canonical_name', 'weight_kg', 'volume_m3', 'unit', 'source_file'];

        foreach ($productColumns as $column) {
            if (! Schema::hasColumn('products', $column)) {
                return false;
            }
        }

        return true;
    }

    protected function backfillProduct(Product $product): array
    {
        $changes = [];

        $canonical = $this->canonicalName($product->name);
        if ($canonical !== $product->canonical_name) {
            $product->canonical_name = $canonical;
            $changes[] = 'canonical_name';
        }

        if ($this->isEmpty($product->sku)) {
            $product->sku = $this->generateSku($product);
            $changes[] = 'sku';
        }

        $inferred = $this->inferAttributes($product->name);
        if ($product->weight_kg === null && isset($inferred['weight_kg'])) {
            $product->weight_kg = $inferred['weight_kg'];
            $changes[] = 'weight_kg';
        }
        if ($product->volume_m3 === null && isset($inferred['volume_m3'])) {
            $product->volume_m3 = $inferred['volume_m3'];
            $changes[] = 'volume_m3';
        }
        if ($this->isEmpty($product->unit) && isset($inferred['unit'])) {
            $product->unit = $inferred['unit'];
            $changes[] = 'unit';
        }

        if ($this->isEmpty($product->source_file)) {
            $source = $this->detectSourceFile($product->name);
            if ($source) {
                $product->source_file = $source;
                $changes[] = 'source_file';
            }
        }

        return $changes;
    }

    protected function canonicalName(?string $name): ?string
    {
        if ($this->isEmpty($name)) {
            return null;
        }

        return mb_strtolower(trim($name));
    }

    protected function generateSku(Product $product): string
    {
        $hash = strtoupper(dechex(crc32($product->name ?? (string) $product->id)));

        return sprintf('PROD-%d-%s', $product->id, $hash);
    }

    /**
     * @return array<string, float|string>|[]
     */
    protected function inferAttributes(?string $name): array
    {
        if ($this->isEmpty($name)) {
            return [];
        }

        $haystack = mb_strtolower($name);
        foreach ($this->attributeRules as $rule) {
            if (mb_strpos($haystack, $rule['pattern']) !== false) {
                return [
                    'weight_kg' => $rule['weight'],
                    'volume_m3' => $rule['volume'],
                    'unit' => $rule['unit'] ?? 'pcs',
                ];
            }
        }

        return [];
    }

    protected function detectSourceFile(?string $name): ?string
    {
        if ($this->isEmpty($name)) {
            return null;
        }

        $needle = mb_strtolower($name);

        foreach ($this->dataFiles as $file) {
            if (str_contains($file['content'], $needle)) {
                return $file['path'];
            }
        }

        return null;
    }

    /**
     * @return array<int, array{path: string, content: string}>
     */
    protected function loadDataFiles(): array
    {
        $files = [];
        $directory = env('CATALOG_DATA_PATH', '/mnt/data');
        if (! File::exists($directory)) {
            // Support local desktop path used in Narvik dataset
            $fallback = '/home/dima/Стільниця/Модули';
            if (File::exists($fallback)) {
                $directory = $fallback;
            } else {
                return $files;
            }
        }

        if (! File::isDirectory($directory)) {
            return $files;
        }

        foreach (File::files($directory) as $file) {
            if ($file->getExtension() !== 'txt') {
                continue;
            }

            $content = mb_strtolower(File::get($file->getPathname()));
            $files[] = [
                'path' => $file->getPathname(),
                'content' => $content,
            ];
        }

        return $files;
    }

    protected function isEmpty(?string $value): bool
    {
        return $value === null || trim($value) === '';
    }

    protected function log($handle, string $message): void
    {
        fwrite($handle, '['.Carbon::now()->toDateTimeString()."] {$message}".PHP_EOL);
    }
}
