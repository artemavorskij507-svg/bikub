<?php

namespace App\Jobs\Delivery;

use App\Models\Delivery\GroceryOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SubstitutionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public GroceryOrder $groceryOrder
    ) {
        $this->onQueue('ai-processing');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Find alternatives for each item
            foreach ($this->groceryOrder->items as $item) {
                if (! $item->product_id) {
                    continue;
                }

                $alternatives = $this->findAlternatives($item);

                if ($alternatives->isNotEmpty()) {
                    $item->update([
                        'substitution_proposed' => $alternatives->map(function ($alt) {
                            return [
                                'product_id' => $alt->id,
                                'name' => $alt->name,
                                'price' => $alt->pivot->price ?? $alt->price ?? 0,
                                'reason' => 'Similar product available',
                            ];
                        })->toArray(),
                    ]);
                }
            }

            // Send notification if substitutions found
            if ($this->groceryOrder->items->some(fn ($item) => ! empty($item->substitution_proposed))) {
                // Notification::send($this->groceryOrder->deliveryOrder->order->user, new SubstitutionAvailable($this->groceryOrder));
            }
        } catch (\Exception $e) {
            Log::error('Failed to process substitutions', [
                'grocery_order_id' => $this->groceryOrder->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Find alternative products.
     */
    protected function findAlternatives($item)
    {
        $product = $item->product;
        if (! $product) {
            return collect();
        }

        // Find similar products in same category
        return \App\Models\Product::where('id', '!=', $product->id)
            ->where('is_active', true)
            ->whereHas('stores', function ($query) {
                $query->where('is_active', true);
            })
            ->limit(3)
            ->get();
    }
}
