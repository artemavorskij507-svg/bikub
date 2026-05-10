<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MovingPhotoEstimateController extends Controller
{
    /**
     * Upload photos for moving estimate (without creating order).
     */
    public function estimate(Request $request): JsonResponse
    {
        $request->validate([
            'photos' => 'required|array|min:1|max:10',
            'photos.*' => 'required|file|mimes:jpg,jpeg,png,webp|max:10240', // 10MB max
        ]);

        try {
            $uploadedPhotos = [];
            $totalVolume = 0;
            $itemsDetected = [];

            foreach ($request->file('photos') as $file) {
                // Store photo temporarily
                $filename = 'estimate_'.time().'_'.Str::random(8).'.'.$file->getClientOriginalExtension();
                $path = $file->storeAs('moving-estimates', $filename, 'public');

                $uploadedPhotos[] = [
                    'filename' => $filename,
                    'url' => Storage::disk('public')->url($path),
                    'size' => $file->getSize(),
                ];

                // Simple analysis based on file size and name (can be enhanced with AI)
                $estimatedVolume = $this->estimateVolumeFromPhoto($file);
                $totalVolume += $estimatedVolume;

                // Detect items (simplified - can use AI vision API)
                $itemsDetected[] = $this->detectItems($file);
            }

            // Calculate estimate based on volume
            $estimate = $this->calculateEstimateFromVolume($totalVolume);

            return response()->json([
                'success' => true,
                'data' => [
                    'photos' => $uploadedPhotos,
                    'estimated_volume' => round($totalVolume, 2),
                    'items_detected' => ! empty($itemsDetected) ? array_unique(array_merge(...$itemsDetected)) : ['Различные предметы'],
                    'estimate' => $estimate,
                    'recommended_package_type' => $this->recommendPackageType($totalVolume),
                ],
                'message' => 'Оценка выполнена успешно',
            ]);

        } catch (\Exception $e) {
            Log::error('Photo estimate failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обработке фотографий',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Simple volume estimation from photo (can be enhanced with AI).
     */
    protected function estimateVolumeFromPhoto($file): float
    {
        // Get image dimensions if possible
        try {
            $imageInfo = getimagesize($file->getRealPath());
            if ($imageInfo) {
                $width = $imageInfo[0];
                $height = $imageInfo[1];
                $pixels = $width * $height;

                // Estimate volume based on image dimensions and file size
                // Larger images with more detail might indicate larger items
                $sizeMB = $file->getSize() / 1024 / 1024;
                $complexity = ($pixels / 1000000) * ($sizeMB / 2); // Normalized complexity

                if ($complexity > 5) {
                    return rand(8, 20); // Large items (furniture, appliances)
                } elseif ($complexity > 2) {
                    return rand(3, 8); // Medium items (boxes, small furniture)
                } else {
                    return rand(0.5, 3); // Small items
                }
            }
        } catch (\Exception $e) {
            // Fallback to file size only
        }

        // Fallback: Simple heuristic based on file size
        $sizeMB = $file->getSize() / 1024 / 1024;
        if ($sizeMB > 5) {
            return rand(5, 15); // Large items
        } elseif ($sizeMB > 2) {
            return rand(2, 5); // Medium items
        } else {
            return rand(0.5, 2); // Small items
        }
    }

    /**
     * Detect items in photo (simplified - can use OpenAI Vision API).
     */
    protected function detectItems($file): array
    {
        // Simplified detection - in production, use AI vision API (OpenAI Vision)
        $items = [];
        $filename = strtolower($file->getClientOriginalName());

        // Check filename for keywords
        if (str_contains($filename, 'furniture') || str_contains($filename, 'мебель') ||
            str_contains($filename, 'sofa') || str_contains($filename, 'диван') ||
            str_contains($filename, 'table') || str_contains($filename, 'стол')) {
            $items[] = 'Мебель';
        }
        if (str_contains($filename, 'box') || str_contains($filename, 'коробка') ||
            str_contains($filename, 'carton')) {
            $items[] = 'Коробки';
        }
        if (str_contains($filename, 'appliance') || str_contains($filename, 'техника') ||
            str_contains($filename, 'fridge') || str_contains($filename, 'холодильник') ||
            str_contains($filename, 'washer') || str_contains($filename, 'стиралка')) {
            $items[] = 'Бытовая техника';
        }
        if (str_contains($filename, 'fragile') || str_contains($filename, 'хрупк') ||
            str_contains($filename, 'glass') || str_contains($filename, 'стекл')) {
            $items[] = 'Хрупкие предметы';
        }

        // Analyze image dimensions for better detection
        try {
            $imageInfo = getimagesize($file->getRealPath());
            if ($imageInfo) {
                $width = $imageInfo[0];
                $height = $imageInfo[1];

                // Large images might indicate large items
                if ($width > 2000 || $height > 2000) {
                    if (! in_array('Мебель', $items)) {
                        $items[] = 'Крупные предметы';
                    }
                }
            }
        } catch (\Exception $e) {
            // Ignore errors
        }

        // Default items if nothing detected
        if (empty($items)) {
            $items = ['Различные предметы'];
        }

        // Remove duplicates
        return array_unique($items);
    }

    /**
     * Calculate estimate from total volume.
     */
    protected function calculateEstimateFromVolume(float $volume): array
    {
        // Base pricing (can be enhanced with MovingPriceCalculator)
        $basePrice = 2500; // Base transport cost
        $pricePerM3 = 500; // Price per cubic meter
        $laborHours = max(2, ceil($volume / 5)); // Estimated hours
        $laborCost = $laborHours * 650; // Per hour rate

        $transportCost = $basePrice + ($volume * $pricePerM3);
        $totalCost = $transportCost + $laborCost;

        return [
            'transport_cost' => round($transportCost, 2),
            'labor_cost' => round($laborCost, 2),
            'total_cost' => round($totalCost, 2),
            'estimated_hours' => $laborHours,
            'currency' => 'NOK',
        ];
    }

    /**
     * Recommend package type based on volume.
     */
    protected function recommendPackageType(float $volume): string
    {
        if ($volume < 10) {
            return 'economy';
        } elseif ($volume < 30) {
            return 'standard';
        } else {
            return 'premium';
        }
    }
}
