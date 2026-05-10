<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ServiceCategory;
use App\Models\ServiceType;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CatalogImportFromFiles extends Command
{
    protected $signature = 'app:catalog-import-files {--dir=/mnt/data : Directory with source *.txt files}';

    protected $description = 'Parse uploaded text files and create draft catalog entries when missing.';

    protected array $productKeywords = [
        'молоко',
        'хлеб',
        'банан',
        'яблок',
        'апельсин',
        'сыр',
        'йогурт',
        'сок',
        'кофе',
        'чай',
        'крупа',
        'овощ',
        'фрукт',
    ];

    public function handle(): int
    {
        $directory = $this->resolveDirectory($this->option('dir'));
        if (! $directory) {
            $this->error('Source directory not found. Please provide --dir pointing to the uploaded files.');

            return Command::FAILURE;
        }

        $importDir = storage_path('imports');
        if (! File::exists($importDir)) {
            File::makeDirectory($importDir, 0755, true);
        }

        $timestamp = Carbon::now()->format('Ymd_His');
        $csvPath = $importDir.DIRECTORY_SEPARATOR."catalog_import_{$timestamp}.csv";
        $csv = fopen($csvPath, 'w');
        fputcsv($csv, [
            'file_path',
            'entry_name',
            'detected_type',
            'record_type',
            'record_id',
            'status',
            'base_price',
            'estimated_minutes',
        ]);

        $created = 0;
        $skipped = 0;

        foreach (File::files($directory) as $file) {
            if (strtolower($file->getExtension()) !== 'txt') {
                continue;
            }

            $entries = $this->parseEntries($file->getPathname());
            $category = $this->mapCategoryFromFilename($file->getFilename());

            foreach ($entries as $entry) {
                $detectedType = $this->detectEntryType($entry['name']);
                $result = $detectedType === 'product'
                    ? $this->createProductEntry($entry, $file->getPathname())
                    : $this->createServiceTypeEntry($entry, $file->getPathname(), $category);

                $created += $result['status'] === 'created' ? 1 : 0;
                $skipped += $result['status'] !== 'created' ? 1 : 0;

                fputcsv($csv, [
                    $file->getPathname(),
                    $entry['name'],
                    $detectedType,
                    $result['record_type'],
                    $result['id'],
                    $result['status'],
                    $entry['base_price'] ?? '',
                    $entry['estimated_minutes'] ?? '',
                ]);
            }
        }

        fclose($csv);

        $this->info("Import finished. Created {$created}, skipped {$skipped}. CSV report: {$csvPath}");

        return Command::SUCCESS;
    }

    protected function resolveDirectory(?string $preferred): ?string
    {
        $paths = array_filter([
            $preferred,
            base_path('mnt/data'),
            storage_path('app/data'),
            '/home/dima/Стільниця/Модули',
        ]);

        foreach ($paths as $path) {
            if ($path && File::isDirectory($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * @return array<int, array{name: string, description: string, base_price: ?float, estimated_minutes: ?int}>
     */
    protected function parseEntries(string $path): array
    {
        $content = File::get($path);
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $lines = preg_split("/\n+/", $content);

        $entries = [];
        foreach ($lines as $line) {
            $clean = trim($line);
            if ($clean === '') {
                continue;
            }

            $clean = preg_replace('/^[\d\.\)\-–•\*]+\s*/u', '', $clean);
            if (mb_strlen($clean) < 3) {
                continue;
            }

            $name = $this->extractName($clean);
            if (mb_strlen($name) < 3 || mb_strlen($name) > 120) {
                continue;
            }

            $entries[] = [
                'name' => $name,
                'description' => $clean,
                'base_price' => $this->extractPrice($clean),
                'estimated_minutes' => $this->extractDuration($clean),
            ];
        }

        $unique = [];
        foreach ($entries as $entry) {
            $key = mb_strtolower($entry['name']);
            if (! isset($unique[$key])) {
                $unique[$key] = $entry;
            }
        }

        return array_values($unique);
    }

    protected function extractName(string $line): string
    {
        $parts = preg_split('/[—\-:]+/u', $line, 2);

        return trim($parts[0] ?? $line);
    }

    protected function extractPrice(string $line): ?float
    {
        if (preg_match('/(\d[\d\s]{1,8})\s*(?:nok|kr|крон)/iu', $line, $matches)) {
            $value = (int) preg_replace('/\s+/', '', $matches[1]);

            return (float) $value;
        }

        if (preg_match('/от\s+(\d[\d\s]{1,8})/iu', $line, $matches)) {
            $value = (int) preg_replace('/\s+/', '', $matches[1]);

            return (float) $value;
        }

        return null;
    }

    protected function extractDuration(string $line): ?int
    {
        if (preg_match('/(\d{1,2})\s*(?:ч|час)/iu', $line, $matches)) {
            return (int) $matches[1] * 60;
        }

        if (preg_match('/(\d{1,3})\s*(?:мин|minute)/iu', $line, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    protected function detectEntryType(string $name): string
    {
        $haystack = mb_strtolower($name);
        foreach ($this->productKeywords as $keyword) {
            if (str_contains($haystack, $keyword)) {
                return 'product';
            }
        }

        return 'service';
    }

    /**
     * @param  array{name: string, description: string, base_price: ?float, estimated_minutes: ?int}  $entry
     * @return array{status: string, record_type: string, id: int|null}
     */
    protected function createProductEntry(array $entry, string $filePath): array
    {
        $canonical = mb_strtolower($entry['name']);
        if (! $this->shouldCreateProduct($canonical)) {
            return ['status' => 'skipped_exists', 'record_type' => 'product', 'id' => null];
        }

        $slugBase = Str::slug($entry['name']) ?: 'product';
        $slug = $this->uniqueSlug('products', $slugBase);

        $product = new Product([
            'name' => $entry['name'],
            'slug' => $slug,
            'description' => $entry['description'],
            'is_active' => false,
            'canonical_name' => $canonical,
            'source_file' => $filePath,
        ]);
        $product->save();

        return ['status' => 'created', 'record_type' => 'product', 'id' => $product->id];
    }

    protected function shouldCreateProduct(string $canonicalName): bool
    {
        return ! Product::where('canonical_name', $canonicalName)->exists();
    }

    /**
     * @param  array{name: string, description: string, base_price: ?float, estimated_minutes: ?int}  $entry
     * @return array{status: string, record_type: string, id: int|null}
     */
    protected function createServiceTypeEntry(array $entry, string $filePath, string $category): array
    {
        $canonicalName = mb_strtolower($entry['name']);
        $slugBase = Str::slug($entry['name']) ?: 'service';
        $slugBase = $this->normalizeSlugBase($slugBase);
        $canonicalCode = $slugBase;

        $exists = ServiceType::where('canonical_code', $canonicalCode)
            ->orWhereRaw('LOWER(name) = ?', [$canonicalName])
            ->exists();

        if ($exists) {
            return ['status' => 'skipped_exists', 'record_type' => 'service_type', 'id' => null];
        }

        $slug = $this->uniqueSlug('service_types', $slugBase);
        $categoryModel = ServiceCategory::where('code', $category)
            ->orWhere('slug', $category)
            ->first();

        $serviceType = new ServiceType([
            'name' => $entry['name'],
            'slug' => $slug,
            'description' => $entry['description'],
            'category' => $category,
            'service_category_id' => $categoryModel?->id,
            'estimated_duration_minutes' => $entry['estimated_minutes'],
            'is_active' => false,
            'default_pricing' => $entry['base_price'] ? ['base_price' => $entry['base_price']] : null,
            'canonical_code' => $canonicalCode,
            'default_pricing_group' => $category,
            'features' => [
                'imported_from' => $filePath,
            ],
        ]);

        $serviceType->save();

        return ['status' => 'created', 'record_type' => 'service_type', 'id' => $serviceType->id];
    }

    protected function mapCategoryFromFilename(string $filename): string
    {
        $name = mb_strtolower($filename);

        return match (true) {
            str_contains($name, 'достав') => 'delivery',
            str_contains($name, 'поруч') => 'errand',
            str_contains($name, 'мастер') => 'handyman',
            str_contains($name, 'переезд') => 'moving',
            str_contains($name, 'соц') => 'social_care',
            str_contains($name, 'эко') => 'eco_disposal',
            str_contains($name, 'эвакуатор') || str_contains($name, 'дорог') => 'roadside',
            default => 'general',
        };
    }

    protected function uniqueSlug(string $table, string $base, string $column = 'slug'): string
    {
        $base = $this->normalizeSlugBase($base ?: 'entry');
        $slug = $base;
        $counter = 1;

        while (DB::table($table)->where($column, $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    protected function normalizeSlugBase(string $base): string
    {
        $base = trim(Str::limit($base, 120, ''));

        if ($base === '') {
            return 'entry';
        }

        return rtrim($base, '-');
    }
}
