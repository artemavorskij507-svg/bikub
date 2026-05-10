<?php

namespace App\Http\Controllers;

use App\Models\ImportJob;
use App\Models\ImportSource;
use App\Models\Restaurant;
use App\Models\RetailStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImportController extends Controller
{
    /**
     * Create import source.
     */
    public function createSource(Request $request)
    {
        $request->validate([
            'partner_id' => 'required|exists:partners,id',
            'type' => 'required|in:csv,xlsx,api',
            'name' => 'required|string|max:255',
            'config' => 'required|array',
        ]);

        $source = ImportSource::create([
            'partner_id' => $request->partner_id,
            'type' => $request->type,
            'name' => $request->name,
            'config' => $request->config,
        ]);

        return response()->json([
            'success' => true,
            'data' => $source,
            'message' => 'Import source created successfully',
        ], 201);
    }

    /**
     * Start import job.
     */
    public function startImport(Request $request)
    {
        $request->validate([
            'source_id' => 'required|exists:import_sources,id',
            'file' => 'required_if:type,csv,xlsx|file|mimes:csv,xlsx|max:10240', // 10MB max
            'data' => 'required_if:type,api|array',
        ]);

        $source = ImportSource::findOrFail($request->source_id);

        // Create import job
        $job = ImportJob::create([
            'source_id' => $source->id,
            'status' => 'queued',
        ]);

        // Handle file upload
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = 'import_'.$job->id.'_'.time().'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs('imports', $filename);

            $job->update([
                'stats' => [
                    'filename' => $filename,
                    'path' => $path,
                    'size' => $file->getSize(),
                ],
            ]);
        }

        // Process import based on type
        $this->processImport($job, $request->data ?? []);

        return response()->json([
            'success' => true,
            'data' => $job,
            'message' => 'Import job started',
        ], 201);
    }

    /**
     * Get import job status and preview.
     */
    public function getJobStatus(string $jobId)
    {
        $job = ImportJob::findOrFail($jobId);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $job->id,
                'status' => $job->status,
                'stats' => $job->stats,
                'preview_data' => $job->preview_data,
                'error' => $job->error,
                'created_at' => $job->created_at,
            ],
        ]);
    }

    /**
     * Apply import changes.
     */
    public function applyImport(string $jobId)
    {
        $job = ImportJob::findOrFail($jobId);

        if ($job->status !== 'done') {
            return response()->json([
                'success' => false,
                'message' => 'Import job is not ready for application',
            ], 400);
        }

        try {
            DB::beginTransaction();

            $previewData = $job->preview_data;
            $stats = $job->stats ?? [];

            $applied = 0;
            $errors = 0;

            foreach ($previewData['changes'] as $change) {
                try {
                    $this->applyChange($change);
                    $applied++;
                } catch (\Exception $e) {
                    $errors++;
                    Log::error('Import apply error', [
                        'job_id' => $job->id,
                        'change' => $change,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $job->update([
                'status' => 'applied',
                'stats' => array_merge($stats, [
                    'applied' => $applied,
                    'errors' => $errors,
                    'applied_at' => now()->toISOString(),
                ]),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'applied' => $applied,
                    'errors' => $errors,
                ],
                'message' => 'Import changes applied successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to apply import changes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Rollback import changes.
     */
    public function rollbackImport(string $jobId)
    {
        $job = ImportJob::findOrFail($jobId);

        if ($job->status !== 'applied') {
            return response()->json([
                'success' => false,
                'message' => 'Import job is not applied, cannot rollback',
            ], 400);
        }

        try {
            DB::beginTransaction();

            $previewData = $job->preview_data;
            $stats = $job->stats ?? [];

            $rolledBack = 0;
            $errors = 0;

            // Rollback in reverse order
            $changes = array_reverse($previewData['changes']);

            foreach ($changes as $change) {
                try {
                    $this->rollbackChange($change);
                    $rolledBack++;
                } catch (\Exception $e) {
                    $errors++;
                    Log::error('Import rollback error', [
                        'job_id' => $job->id,
                        'change' => $change,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $job->update([
                'status' => 'rolled_back',
                'stats' => array_merge($stats, [
                    'rolled_back' => $rolledBack,
                    'rollback_errors' => $errors,
                    'rolled_back_at' => now()->toISOString(),
                ]),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'rolled_back' => $rolledBack,
                    'errors' => $errors,
                ],
                'message' => 'Import changes rolled back successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to rollback import changes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process import job.
     */
    private function processImport(ImportJob $job, array $apiData = [])
    {
        $job->update(['status' => 'running']);

        try {
            $source = $job->source;
            $config = $source->config;

            $data = match ($source->type) {
                'csv' => $this->parseCsvFile($job),
                'xlsx' => $this->parseXlsxFile($job),
                'api' => $apiData,
                default => throw new \Exception('Unsupported import type'),
            };

            $changes = $this->analyzeChanges($data, $config, $source->partner_id);

            $job->update([
                'status' => 'done',
                'preview_data' => [
                    'total_items' => count($data),
                    'changes' => $changes,
                    'summary' => $this->generateSummary($changes),
                ],
                'stats' => array_merge($job->stats ?? [], [
                    'processed_at' => now()->toISOString(),
                    'total_items' => count($data),
                ]),
            ]);

        } catch (\Exception $e) {
            $job->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Parse CSV file.
     */
    private function parseCsvFile(ImportJob $job): array
    {
        $stats = $job->stats;
        $filePath = Storage::path($stats['path']);

        $data = [];
        $handle = fopen($filePath, 'r');

        if (! $handle) {
            throw new \Exception('Cannot open CSV file');
        }

        $headers = fgetcsv($handle);

        while (($row = fgetcsv($handle)) !== false) {
            $data[] = array_combine($headers, $row);
        }

        fclose($handle);

        return $data;
    }

    /**
     * Parse XLSX file.
     */
    private function parseXlsxFile(ImportJob $job): array
    {
        // For now, return empty array - would need PhpSpreadsheet package
        // In production, implement XLSX parsing
        return [];
    }

    /**
     * Analyze changes between imported data and existing data.
     */
    private function analyzeChanges(array $data, array $config, int $partnerId): array
    {
        $changes = [];
        $modelType = $config['model_type'] ?? 'restaurant'; // restaurant or retail_store

        foreach ($data as $row) {
            $externalId = $row[$config['external_id_field']] ?? null;
            $hash = $this->generateHash($row);

            if (! $externalId) {
                continue;
            }

            $existing = match ($modelType) {
                'restaurant' => Restaurant::where('external_id', $externalId)->first(),
                'retail_store' => RetailStore::where('external_id', $externalId)->first(),
                default => null,
            };

            if ($existing) {
                // Check if data has changed
                if ($existing->hash !== $hash) {
                    $changes[] = [
                        'action' => 'update',
                        'model_type' => $modelType,
                        'id' => $existing->id,
                        'external_id' => $externalId,
                        'old_data' => $this->extractRelevantFields($existing->toArray(), $config),
                        'new_data' => $this->extractRelevantFields($row, $config),
                    ];
                }
            } else {
                // New item
                $changes[] = [
                    'action' => 'create',
                    'model_type' => $modelType,
                    'external_id' => $externalId,
                    'new_data' => $this->extractRelevantFields($row, $config),
                ];
            }
        }

        return $changes;
    }

    /**
     * Apply a single change.
     */
    private function applyChange(array $change): void
    {
        $data = $change['new_data'];
        $data['hash'] = $this->generateHash($data);
        $data['last_seen_at'] = now();
        $data['source'] = 'import';

        match ($change['action']) {
            'create' => $this->createItem($change['model_type'], $data),
            'update' => $this->updateItem($change['model_type'], $change['id'], $data),
            default => throw new \Exception('Unknown action'),
        };
    }

    /**
     * Rollback a single change.
     */
    private function rollbackChange(array $change): void
    {
        match ($change['action']) {
            'create' => $this->deleteItem($change['model_type'], $change['external_id']),
            'update' => $this->restoreItem($change['model_type'], $change['id'], $change['old_data']),
            default => throw new \Exception('Unknown action'),
        };
    }

    /**
     * Create new item.
     */
    private function createItem(string $modelType, array $data): void
    {
        match ($modelType) {
            'restaurant' => Restaurant::create($data),
            'retail_store' => RetailStore::create($data),
            default => throw new \Exception('Unknown model type'),
        };
    }

    /**
     * Update existing item.
     */
    private function updateItem(string $modelType, int $id, array $data): void
    {
        $model = match ($modelType) {
            'restaurant' => Restaurant::findOrFail($id),
            'retail_store' => RetailStore::findOrFail($id),
            default => throw new \Exception('Unknown model type'),
        };

        $model->update($data);
    }

    /**
     * Delete item.
     */
    private function deleteItem(string $modelType, string $externalId): void
    {
        match ($modelType) {
            'restaurant' => Restaurant::where('external_id', $externalId)->delete(),
            'retail_store' => RetailStore::where('external_id', $externalId)->delete(),
            default => throw new \Exception('Unknown model type'),
        };
    }

    /**
     * Restore item to previous state.
     */
    private function restoreItem(string $modelType, int $id, array $oldData): void
    {
        $model = match ($modelType) {
            'restaurant' => Restaurant::findOrFail($id),
            'retail_store' => RetailStore::findOrFail($id),
            default => throw new \Exception('Unknown model type'),
        };

        $model->update($oldData);
    }

    /**
     * Generate hash for data comparison.
     */
    private function generateHash(array $data): string
    {
        // Remove non-relevant fields for comparison
        $relevantData = array_filter($data, function ($key) {
            return ! in_array($key, ['id', 'created_at', 'updated_at', 'hash', 'last_seen_at']);
        }, ARRAY_FILTER_USE_KEY);

        return md5(serialize($relevantData));
    }

    /**
     * Extract relevant fields based on config.
     */
    private function extractRelevantFields(array $data, array $config): array
    {
        $fieldMapping = $config['field_mapping'] ?? [];
        $result = [];

        foreach ($fieldMapping as $sourceField => $targetField) {
            if (isset($data[$sourceField])) {
                $result[$targetField] = $data[$sourceField];
            }
        }

        return $result;
    }

    /**
     * Generate summary of changes.
     */
    private function generateSummary(array $changes): array
    {
        $summary = [
            'total' => count($changes),
            'create' => 0,
            'update' => 0,
            'delete' => 0,
        ];

        foreach ($changes as $change) {
            $summary[$change['action']]++;
        }

        return $summary;
    }
}
