<?php

namespace App\Filament\Resources\ErrandOrderDetailsResource\Pages;

use App\Filament\Resources\ErrandOrderDetailsResource;
use App\Models\ErrandOrderDetails;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class ListErrandOrderDetails extends ListRecords
{
    protected static string $resource = ErrandOrderDetailsResource::class;

    public function mount(): void
    {
        $this->ensureErrandTableForLocal();

        parent::mount();

        $this->seedErrandRowsIfEmpty();
    }

    protected function getActions(): array
    {
        return [];
    }

    protected function ensureErrandTableForLocal(): void
    {
        if (! app()->environment('local') || Schema::hasTable('errand_order_details')) {
            return;
        }

        Schema::create('errand_order_details', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->string('from_address')->nullable();
            $table->string('to_address')->nullable();
            $table->decimal('from_lat', 10, 7)->nullable();
            $table->decimal('from_lng', 10, 7)->nullable();
            $table->decimal('to_lat', 10, 7)->nullable();
            $table->decimal('to_lng', 10, 7)->nullable();
            $table->json('waypoints')->nullable();
            $table->json('contacts')->nullable();
            $table->timestamp('desired_start_at')->nullable();
            $table->timestamp('desired_finish_at')->nullable();
            $table->boolean('is_urgent')->default(false);
            $table->boolean('requires_signature')->default(false);
            $table->boolean('requires_trusted_helper')->default(false);
            $table->boolean('involves_documents')->default(false);
            $table->unsignedTinyInteger('complexity_level')->nullable();
            $table->unsignedInteger('expected_duration_minutes')->nullable();
            $table->unsignedInteger('material_advance_amount')->nullable();
            $table->unsignedInteger('base_fee')->nullable();
            $table->unsignedInteger('distance_fee')->nullable();
            $table->unsignedInteger('time_fee')->nullable();
            $table->unsignedInteger('complexity_fee')->nullable();
            $table->unsignedInteger('trusted_helper_fee')->nullable();
            $table->unsignedInteger('urgency_fee')->nullable();
            $table->unsignedInteger('total_estimated_price')->nullable();
            $table->unsignedBigInteger('dispatcher_id')->nullable();
            $table->unsignedBigInteger('executor_profile_id')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    protected function seedErrandRowsIfEmpty(): void
    {
        if (! app()->environment('local') || ! Schema::hasTable('errand_order_details')) {
            return;
        }

        if (ErrandOrderDetails::query()->exists()) {
            return;
        }

        try {
            $orderId = Schema::hasTable('orders') ? DB::table('orders')->value('id') : null;

            ErrandOrderDetails::query()->create([
                'order_id' => $orderId,
                'category' => 'purchase_and_deliver',
                'description' => 'Local demo errand task',
                'from_address' => 'Oslo Sentrum',
                'to_address' => 'Karl Johans gate 1, Oslo',
                'is_urgent' => false,
                'requires_signature' => false,
                'requires_trusted_helper' => false,
                'involves_documents' => false,
                'complexity_level' => 2,
                'expected_duration_minutes' => 40,
                'total_estimated_price' => 12900,
                'contacts' => [
                    'name' => 'Demo Client',
                    'phone' => '+47-000-00-000',
                ],
                'waypoints' => [
                    ['address' => 'Aker Brygge, Oslo'],
                ],
                'meta' => [
                    'source' => 'local_demo_seed',
                    'seed' => (string) Str::uuid(),
                ],
            ]);
        } catch (Throwable) {
            // Keep admin list usable even on custom local schemas.
        }
    }
}
