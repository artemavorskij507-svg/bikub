<?php

namespace App\Filament\Resources\WebhookLogResource\Pages;

use App\Filament\Resources\WebhookLogResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ListWebhookLogs extends ListRecords
{
    protected static string $resource = WebhookLogResource::class;

    public function mount(): void
    {
        parent::mount();

        $this->ensureLocalWebhookSchema();
        $this->seedLocalWebhookLogsIfEmpty();
    }

    protected function getTitle(): string
    {
        return 'Webhook Center';
    }

    protected function ensureLocalWebhookSchema(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        if (! Schema::hasTable('webhook_logs')) {
            Schema::create('webhook_logs', function (Blueprint $table) {
                $table->id();
                $table->string('provider')->nullable();
                $table->string('event_type')->nullable();
                $table->string('external_id')->nullable();
                $table->string('status')->default('received');
                $table->integer('http_status')->nullable();
                $table->json('payload')->nullable();
                $table->text('error_message')->nullable();
                $table->string('request_id')->nullable();
                $table->timestamp('received_at')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->unsignedInteger('attempt')->default(0);
                $table->unsignedBigInteger('order_id')->nullable();
                $table->unsignedBigInteger('payment_id')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });

            return;
        }

        if (! Schema::hasColumn('webhook_logs', 'provider')) {
            Schema::table('webhook_logs', fn (Blueprint $table) => $table->string('provider')->nullable());
        }
        if (! Schema::hasColumn('webhook_logs', 'event_type')) {
            Schema::table('webhook_logs', fn (Blueprint $table) => $table->string('event_type')->nullable());
        }
        if (! Schema::hasColumn('webhook_logs', 'external_id')) {
            Schema::table('webhook_logs', fn (Blueprint $table) => $table->string('external_id')->nullable());
        }
        if (! Schema::hasColumn('webhook_logs', 'status')) {
            Schema::table('webhook_logs', fn (Blueprint $table) => $table->string('status')->default('received'));
        }
        if (! Schema::hasColumn('webhook_logs', 'http_status')) {
            Schema::table('webhook_logs', fn (Blueprint $table) => $table->integer('http_status')->nullable());
        }
        if (! Schema::hasColumn('webhook_logs', 'payload')) {
            Schema::table('webhook_logs', fn (Blueprint $table) => $table->json('payload')->nullable());
        }
        if (! Schema::hasColumn('webhook_logs', 'error_message')) {
            Schema::table('webhook_logs', fn (Blueprint $table) => $table->text('error_message')->nullable());
        }
        if (! Schema::hasColumn('webhook_logs', 'request_id')) {
            Schema::table('webhook_logs', fn (Blueprint $table) => $table->string('request_id')->nullable());
        }
        if (! Schema::hasColumn('webhook_logs', 'received_at')) {
            Schema::table('webhook_logs', fn (Blueprint $table) => $table->timestamp('received_at')->nullable());
        }
        if (! Schema::hasColumn('webhook_logs', 'processed_at')) {
            Schema::table('webhook_logs', fn (Blueprint $table) => $table->timestamp('processed_at')->nullable());
        }
        if (! Schema::hasColumn('webhook_logs', 'attempt')) {
            Schema::table('webhook_logs', fn (Blueprint $table) => $table->unsignedInteger('attempt')->default(0));
        }
        if (! Schema::hasColumn('webhook_logs', 'order_id')) {
            Schema::table('webhook_logs', fn (Blueprint $table) => $table->unsignedBigInteger('order_id')->nullable());
        }
        if (! Schema::hasColumn('webhook_logs', 'payment_id')) {
            Schema::table('webhook_logs', fn (Blueprint $table) => $table->unsignedBigInteger('payment_id')->nullable());
        }
        if (! Schema::hasColumn('webhook_logs', 'metadata')) {
            Schema::table('webhook_logs', fn (Blueprint $table) => $table->json('metadata')->nullable());
        }
        if (! Schema::hasColumn('webhook_logs', 'created_at')) {
            Schema::table('webhook_logs', fn (Blueprint $table) => $table->timestamp('created_at')->nullable());
        }
        if (! Schema::hasColumn('webhook_logs', 'updated_at')) {
            Schema::table('webhook_logs', fn (Blueprint $table) => $table->timestamp('updated_at')->nullable());
        }
    }

    protected function seedLocalWebhookLogsIfEmpty(): void
    {
        if (! app()->environment('local') || ! Schema::hasTable('webhook_logs')) {
            return;
        }

        if (DB::table('webhook_logs')->exists()) {
            return;
        }

        $now = now();
        $baseId = 1;

        if (Schema::hasColumn('webhook_logs', 'id')) {
            $maxId = (int) (DB::table('webhook_logs')->max('id') ?? 0);
            $baseId = $maxId + 1;
        }

        $rows = [
            [
                'provider' => 'stripe',
                'event_type' => 'payment_intent.succeeded',
                'external_id' => 'pi_demo_001',
                'status' => 'processed',
                'http_status' => 200,
                'payload' => json_encode(['id' => 'evt_demo_001', 'type' => 'payment_intent.succeeded']),
                'request_id' => (string) \Illuminate\Support\Str::uuid(),
                'received_at' => $now->copy()->subMinutes(20),
                'processed_at' => $now->copy()->subMinutes(19),
                'attempt' => 1,
                'metadata' => json_encode(['source' => 'local_demo_seed']),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'provider' => 'n8n',
                'event_type' => 'order.updated',
                'external_id' => 'ord_demo_442',
                'status' => 'received',
                'http_status' => 202,
                'payload' => json_encode(['id' => 'evt_demo_002', 'type' => 'order.updated']),
                'request_id' => (string) \Illuminate\Support\Str::uuid(),
                'received_at' => $now->copy()->subMinutes(8),
                'processed_at' => null,
                'attempt' => 0,
                'metadata' => json_encode(['source' => 'local_demo_seed']),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'provider' => 'internal',
                'event_type' => 'delivery.failed',
                'external_id' => 'del_demo_013',
                'status' => 'failed',
                'http_status' => 500,
                'payload' => json_encode(['id' => 'evt_demo_003', 'type' => 'delivery.failed']),
                'error_message' => 'Timeout while dispatching webhook consumer',
                'request_id' => (string) \Illuminate\Support\Str::uuid(),
                'received_at' => $now->copy()->subMinutes(3),
                'processed_at' => $now->copy()->subMinutes(2),
                'attempt' => 2,
                'metadata' => json_encode(['source' => 'local_demo_seed']),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        $restoreForeignKeys = false;

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
            $restoreForeignKeys = true;
        }

        try {
            foreach ($rows as $index => $row) {
                $preparedRow = $this->prepareWebhookRowForInsert($row, $baseId + $index);
                if (empty($preparedRow)) {
                    continue;
                }

                try {
                    DB::table('webhook_logs')->insert($preparedRow);
                } catch (\Throwable $e) {
                    Log::warning('Skipping webhook local demo seed row due to constraint mismatch.', [
                        'error' => $e->getMessage(),
                    ]);

                    continue;
                }
            }
        } finally {
            if ($restoreForeignKeys) {
                DB::statement('PRAGMA foreign_keys = ON');
            }
        }
    }

    protected function prepareWebhookRowForInsert(array $row, int $id): array
    {
        // SQLite-specific PRAGMA cannot be used on PostgreSQL/MySQL.
        // For non-sqlite drivers, keep only existing columns and rely on DB defaults.
        if (DB::getDriverName() !== 'sqlite') {
            $columnNames = Schema::getColumnListing('webhook_logs');

            if (in_array('id', $columnNames, true)) {
                $row['id'] = $id;
            }

            return array_intersect_key($row, array_flip($columnNames));
        }

        $columns = collect(DB::select("PRAGMA table_info('webhook_logs')"));
        $columnNames = $columns->pluck('name')->all();
        $columnMap = $columns->keyBy(fn ($column) => (string) ($column->name ?? ''));

        if (in_array('id', $columnNames, true)) {
            $row['id'] = $id;
        }

        $foreignKeys = collect(DB::select("PRAGMA foreign_key_list('webhook_logs')"));

        foreach ($foreignKeys as $fk) {
            $from = (string) ($fk->from ?? '');
            $table = (string) ($fk->table ?? '');
            $to = (string) ($fk->to ?? 'id');

            if ($from === '' || $table === '') {
                continue;
            }

            $existing = $row[$from] ?? null;
            if ($existing !== null && $existing !== '' && $existing !== 0 && $existing !== '0') {
                continue;
            }

            $resolved = $this->resolveForeignKeyValue($table, $to);

            $isNotNull = ((int) ($columnMap[$from]->notnull ?? 0)) === 1;
            if ($resolved === null && $isNotNull) {
                $row[$from] = $this->fallbackValueForColumn($from, (string) ($columnMap[$from]->type ?? ''));
                continue;
            }

            if ($resolved !== null) {
                $row[$from] = $resolved;
            }
        }

        foreach ($columns as $column) {
            $name = (string) ($column->name ?? '');
            if ($name === '' || array_key_exists($name, $row)) {
                continue;
            }

            $notNull = (int) ($column->notnull ?? 0) === 1;
            $defaultValue = $column->dflt_value ?? null;

            if ($notNull && $defaultValue === null) {
                $row[$name] = $this->fallbackValueForColumn($name, (string) ($column->type ?? ''));
            }
        }

        return array_intersect_key($row, array_flip($columnNames));
    }

    protected function resolveForeignKeyValue(string $table, string $column): int|string|null
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return null;
        }

        return DB::table($table)->orderBy($column)->value($column);
    }

    protected function fallbackValueForColumn(string $name, string $type): mixed
    {
        $upperType = strtoupper($type);

        if (in_array($name, ['created_at', 'updated_at', 'received_at', 'processed_at'], true)) {
            return now();
        }

        if (str_contains($upperType, 'INT')) {
            return 0;
        }

        if (str_contains($upperType, 'JSON')) {
            return '{}';
        }

        if (str_contains($upperType, 'REAL') || str_contains($upperType, 'FLOA') || str_contains($upperType, 'DOUB') || str_contains($upperType, 'DEC')) {
            return 0;
        }

        if (str_contains($upperType, 'CHAR') || str_contains($upperType, 'TEXT') || str_contains($upperType, 'CLOB') || $upperType === '') {
            return 'local_demo';
        }

        return 0;
    }
}
