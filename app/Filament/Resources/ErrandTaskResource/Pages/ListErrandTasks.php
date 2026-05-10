<?php

namespace App\Filament\Resources\ErrandTaskResource\Pages;

use App\Filament\Resources\ErrandTaskResource;
use App\Models\ErrandTask;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ListErrandTasks extends ListRecords
{
    protected static string $resource = ErrandTaskResource::class;

    public function mount(): void
    {
        $this->ensureLocalErrandTasksSchema();
        $this->seedLocalErrandTasksIfEmpty();

        parent::mount();
    }

    protected function getActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    protected function ensureLocalErrandTasksSchema(): void
    {
        $host = request()->getHost();
        $isLocalRuntime = in_array($host, ['127.0.0.1', 'localhost'], true) || (bool) config('app.debug');

        if (! $isLocalRuntime || Schema::hasTable('errand_tasks')) {
            return;
        }

        Schema::create('errand_tasks', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->string('title')->nullable();
            $table->string('category', 64)->nullable();
            $table->string('status', 32)->default('draft');
            $table->string('priority', 32)->nullable();
            $table->string('customer_name', 120)->nullable();
            $table->string('customer_phone', 32)->nullable();
            $table->string('pickup_address')->nullable();
            $table->string('dropoff_address')->nullable();
            $table->boolean('is_urgent')->default(false);
            $table->unsignedBigInteger('executor_profile_id')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->decimal('estimated_total_amount', 12, 2)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    protected function seedLocalErrandTasksIfEmpty(): void
    {
        $host = request()->getHost();
        $isLocalRuntime = in_array($host, ['127.0.0.1', 'localhost'], true) || (bool) config('app.debug');

        if (! $isLocalRuntime || ! Schema::hasTable('errand_tasks')) {
            return;
        }

        if (DB::table('errand_tasks')->exists()) {
            return;
        }

        $columns = $this->getDbTableColumns('errand_tasks');
        if (empty($columns)) {
            return;
        }

        $row = [
            'title' => 'Demo errand task',
            'category' => 'pickup_and_drop',
            'status' => 'pending',
            'priority' => 'normal',
            'customer_name' => 'Demo Client',
            'customer_phone' => '+47 000 00 000',
            'pickup_address' => 'Oslo Central Station',
            'dropoff_address' => 'Bikube Office',
            'description' => 'Auto-seeded local task for admin table.',
            'is_urgent' => false,
            'scheduled_at' => now()->addHour(),
            'estimated_total_amount' => 299.00,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (in_array('order_id', $columns, true)) {
            $orderId = Schema::hasTable('orders') ? DB::table('orders')->value('id') : null;
            if ($orderId === null) {
                return;
            }
            $row['order_id'] = $orderId;
        }

        if (in_array('id', $columns, true)) {
            $idType = strtolower((string) $this->getColumnType('errand_tasks', 'id'));
            if (str_contains($idType, 'uuid')) {
                $row['id'] = (string) Str::uuid();
            }
        }

        $insert = array_intersect_key($row, array_flip($columns));
        DB::table('errand_tasks')->insert($insert);
    }

    protected function getDbTableColumns(string $table): array
    {
        if (DB::getDriverName() === 'sqlite') {
            return collect(DB::select("PRAGMA table_info('{$table}')"))
                ->pluck('name')
                ->filter()
                ->values()
                ->all();
        }

        return Schema::getColumnListing($table);
    }

    protected function getColumnType(string $table, string $column): string
    {
        if (DB::getDriverName() === 'sqlite') {
            $columnInfo = collect(DB::select("PRAGMA table_info('{$table}')"))
                ->firstWhere('name', $column);

            return (string) ($columnInfo->type ?? '');
        }

        return (string) Schema::getColumnType($table, $column);
    }
}
