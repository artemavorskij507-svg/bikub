<?php

namespace App\Jobs;

use App\Modules\Classifieds\Models\AdCategory;
use App\Modules\Classifieds\Models\AdImport;
use App\Modules\Classifieds\Models\ClassifiedAd;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProcessAdImportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 600; // 10 minutes max

    public function __construct(public AdImport $import) {}

    public function handle(): void
    {
        $this->import->update(['status' => 'processing']);

        $path = Storage::path($this->import->file_path);
        if (! file_exists($path)) {
            $this->import->update([
                'status' => 'failed',
                'report' => ['error' => 'File not found'],
            ]);

            return;
        }

        try {
            $xml = @simplexml_load_file($path);

            if (! $xml) {
                throw new \RuntimeException('Invalid XML file');
            }

            $count = 0;
            $errors = 0;
            $log = [];

            // Expected structure: <root><ad><title>...</title>...</ad></root>
            foreach ($xml->ad as $node) {
                try {
                    $this->processNode($node);
                    $count++;
                } catch (\Throwable $e) {
                    $errors++;
                    if (count($log) < 50) {
                        $log[] = 'Item error: '.$e->getMessage();
                    }
                }
            }

            $this->import->update([
                'status' => 'completed',
                'processed_count' => $count,
                'error_count' => $errors,
                'report' => $log,
            ]);
        } catch (\Throwable $e) {
            $this->import->update([
                'status' => 'failed',
                'report' => ['critical_error' => $e->getMessage()],
            ]);
        }
    }

    protected function processNode(\SimpleXMLElement $node): void
    {
        $title = trim((string) $node->title);
        $description = (string) $node->description;
        $priceRaw = (string) $node->price;
        $price = is_numeric($priceRaw) ? (float) $priceRaw : 0.0;

        $catName = trim((string) $node->category);

        if ($title === '') {
            throw new \RuntimeException('Empty title');
        }

        $category = AdCategory::where('name', 'ilike', $catName)->first();
        if (! $category) {
            throw new \RuntimeException("Category '{$catName}' not found");
        }

        ClassifiedAd::create([
            'user_id' => $this->import->user_id,
            'shop_id' => $this->import->shop_id,
            'category_id' => $category->id,
            'title' => $title,
            'description' => $description,
            'price_value' => (int) round($price * 100),
            'status' => 'published',
            'slug' => Str::slug($title.'-'.Str::random(6)),
            'published_at' => now(),
            'address' => (string) ($node->address ?? ''),
        ]);
    }
}
