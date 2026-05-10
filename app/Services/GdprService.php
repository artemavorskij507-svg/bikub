<?php

namespace App\Services;

use App\Models\GdprRequest;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class GdprService
{
    public function createRequest(string $userId, string $type, ?string $description = null, ?string $orgId = null): GdprRequest
    {
        return GdprRequest::create([
            'user_id' => $userId,
            'org_id' => $orgId,
            'type' => $type,
            'status' => 'pending',
            'description' => $description,
        ]);
    }

    public function processExportRequest(GdprRequest $request): string
    {
        try {
            $request->update(['status' => 'processing']);

            $user = User::findOrFail($request->user_id);
            $orgId = $request->org_id;

            // Collect all user data
            $userData = $this->collectUserData($user, $orgId);

            // Generate export file
            $exportPath = $this->generateExportFile($userData, $user->id);

            // Update request with result
            $request->update([
                'status' => 'completed',
                'result_url' => $exportPath,
                'resolved_at' => now(),
            ]);

            return $exportPath;

        } catch (\Exception $e) {
            Log::error('GDPR export failed', [
                'request_id' => $request->id,
                'user_id' => $request->user_id,
                'error' => $e->getMessage(),
            ]);

            $request->update([
                'status' => 'failed',
                'metadata' => ['error' => $e->getMessage()],
            ]);

            throw $e;
        }
    }

    public function processEraseRequest(GdprRequest $request): bool
    {
        try {
            $request->update(['status' => 'processing']);

            $user = User::findOrFail($request->user_id);
            $orgId = $request->org_id;

            // Anonymize user data instead of deleting
            $this->anonymizeUserData($user, $orgId);

            // Log the erasure
            $this->logDataErasure($user, $orgId);

            $request->update([
                'status' => 'completed',
                'resolved_at' => now(),
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('GDPR erasure failed', [
                'request_id' => $request->id,
                'user_id' => $request->user_id,
                'error' => $e->getMessage(),
            ]);

            $request->update([
                'status' => 'failed',
                'metadata' => ['error' => $e->getMessage()],
            ]);

            throw $e;
        }
    }

    public function processRectifyRequest(GdprRequest $request, array $corrections): bool
    {
        try {
            $request->update(['status' => 'processing']);

            $user = User::findOrFail($request->user_id);
            $orgId = $request->org_id;

            // Apply corrections
            $this->applyDataCorrections($user, $corrections, $orgId);

            // Log the rectification
            $this->logDataRectification($user, $corrections, $orgId);

            $request->update([
                'status' => 'completed',
                'metadata' => ['corrections' => $corrections],
                'resolved_at' => now(),
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('GDPR rectification failed', [
                'request_id' => $request->id,
                'user_id' => $request->user_id,
                'error' => $e->getMessage(),
            ]);

            $request->update([
                'status' => 'failed',
                'metadata' => ['error' => $e->getMessage()],
            ]);

            throw $e;
        }
    }

    public function processPortabilityRequest(GdprRequest $request): string
    {
        try {
            $request->update(['status' => 'processing']);

            $user = User::findOrFail($request->user_id);
            $orgId = $request->org_id;

            // Collect portable data
            $portableData = $this->collectPortableData($user, $orgId);

            // Generate portable file
            $portablePath = $this->generatePortableFile($portableData, $user->id);

            $request->update([
                'status' => 'completed',
                'result_url' => $portablePath,
                'resolved_at' => now(),
            ]);

            return $portablePath;

        } catch (\Exception $e) {
            Log::error('GDPR portability failed', [
                'request_id' => $request->id,
                'user_id' => $request->user_id,
                'error' => $e->getMessage(),
            ]);

            $request->update([
                'status' => 'failed',
                'metadata' => ['error' => $e->getMessage()],
            ]);

            throw $e;
        }
    }

    public function getPendingRequests(?string $orgId = null): array
    {
        $query = GdprRequest::where('status', 'pending');

        if ($orgId) {
            $query->where('org_id', $orgId);
        }

        return $query->with('user')
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    public function getRequestHistory(string $userId): array
    {
        return GdprRequest::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    public function anonymizePiiInLogs(?string $orgId = null): int
    {
        $anonymizedCount = 0;

        // This would typically involve scanning log files and replacing PII
        // For now, we'll simulate the process

        $logFiles = $this->getLogFiles($orgId);

        foreach ($logFiles as $logFile) {
            $content = file_get_contents($logFile);
            $originalContent = $content;

            // Anonymize email addresses
            $content = preg_replace('/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/', '[EMAIL_REDACTED]', $content);

            // Anonymize phone numbers
            $content = preg_replace('/\b\d{3}[-.]?\d{3}[-.]?\d{4}\b/', '[PHONE_REDACTED]', $content);

            // Anonymize credit card numbers
            $content = preg_replace('/\b\d{4}[-\s]?\d{4}[-\s]?\d{4}[-\s]?\d{4}\b/', '[CARD_REDACTED]', $content);

            if ($content !== $originalContent) {
                file_put_contents($logFile, $content);
                $anonymizedCount++;
            }
        }

        return $anonymizedCount;
    }

    public function generateDataRetentionReport(?string $orgId = null): array
    {
        $report = [
            'generated_at' => now(),
            'organization_id' => $orgId,
            'data_categories' => [],
        ];

        // User data retention
        $report['data_categories']['users'] = [
            'retention_period' => '7 years',
            'current_count' => User::when($orgId, function ($q) use ($orgId) {
                return $q->whereHas('organizations', function ($q) use ($orgId) {
                    $q->where('organizations.id', $orgId);
                });
            })->count(),
            'expired_count' => $this->getExpiredUserCount($orgId),
            'next_cleanup' => $this->getNextCleanupDate('users'),
        ];

        // Order data retention
        $report['data_categories']['orders'] = [
            'retention_period' => '5 years',
            'current_count' => \App\Models\Order::when($orgId, function ($q) use ($orgId) {
                return $q->where('org_id', $orgId);
            })->count(),
            'expired_count' => $this->getExpiredOrderCount($orgId),
            'next_cleanup' => $this->getNextCleanupDate('orders'),
        ];

        // Log data retention
        $report['data_categories']['logs'] = [
            'retention_period' => '1 year',
            'current_count' => $this->getLogFileCount($orgId),
            'expired_count' => $this->getExpiredLogCount($orgId),
            'next_cleanup' => $this->getNextCleanupDate('logs'),
        ];

        return $report;
    }

    private function collectUserData(User $user, ?string $orgId = null): array
    {
        $data = [
            'user_profile' => $user->toArray(),
            'orders' => [],
            'payments' => [],
            'reviews' => [],
            'subscriptions' => [],
            'loyalty_data' => [],
            'device_data' => [],
            'preferences' => [],
        ];

        // Collect orders
        $ordersQuery = $user->orders();
        if ($orgId) {
            $ordersQuery->where('org_id', $orgId);
        }
        $data['orders'] = $ordersQuery->get()->toArray();

        // Collect payments
        $data['payments'] = $user->payments()->get()->toArray();

        // Collect reviews
        $data['reviews'] = $user->reviews()->get()->toArray();

        // Collect subscriptions
        $data['subscriptions'] = $user->subscriptions()->get()->toArray();

        // Collect loyalty data
        $data['loyalty_data'] = $user->loyaltyWallet()->get()->toArray();

        // Collect device data
        $data['device_data'] = $user->devices()->get()->toArray();

        // Collect preferences
        $data['preferences'] = $user->notificationPreferences()->get()->toArray();

        return $data;
    }

    private function generateExportFile(array $userData, string $userId): string
    {
        $filename = "gdpr_export_{$userId}_".now()->format('Y-m-d_H-i-s').'.zip';
        $filepath = storage_path('app/gdpr-exports/'.$filename);

        // Ensure directory exists
        if (! file_exists(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }

        $zip = new ZipArchive;
        if ($zip->open($filepath, ZipArchive::CREATE) === true) {
            // Add user data as JSON
            $zip->addFromString('user_data.json', json_encode($userData, JSON_PRETTY_PRINT));

            // Add individual CSV files for each data type
            foreach ($userData as $type => $data) {
                if (is_array($data) && ! empty($data)) {
                    $csv = $this->arrayToCsv($data);
                    $zip->addFromString("{$type}.csv", $csv);
                }
            }

            $zip->close();
        }

        return $filename;
    }

    private function anonymizeUserData(User $user, ?string $orgId = null): void
    {
        // Anonymize user profile
        $user->update([
            'name' => 'Anonymized User',
            'email' => 'anonymized_'.$user->id.'@deleted.local',
            'phone' => null,
            'address' => null,
            'deleted_at' => now(),
        ]);

        // Anonymize orders
        $ordersQuery = $user->orders();
        if ($orgId) {
            $ordersQuery->where('org_id', $orgId);
        }
        $ordersQuery->update([
            'customer_name' => 'Anonymized',
            'customer_phone' => null,
            'customer_email' => 'anonymized@deleted.local',
            'delivery_address' => 'Anonymized Address',
        ]);

        // Anonymize reviews
        $user->reviews()->update([
            'text' => '[Content removed due to GDPR request]',
        ]);
    }

    private function logDataErasure(User $user, ?string $orgId = null): void
    {
        Log::info('GDPR Data Erasure', [
            'user_id' => $user->id,
            'org_id' => $orgId,
            'erased_at' => now(),
            'action' => 'anonymize',
        ]);
    }

    private function applyDataCorrections(User $user, array $corrections, ?string $orgId = null): void
    {
        foreach ($corrections as $field => $value) {
            if (in_array($field, ['name', 'email', 'phone', 'address'])) {
                $user->update([$field => $value]);
            }
        }
    }

    private function logDataRectification(User $user, array $corrections, ?string $orgId = null): void
    {
        Log::info('GDPR Data Rectification', [
            'user_id' => $user->id,
            'org_id' => $orgId,
            'corrections' => $corrections,
            'rectified_at' => now(),
        ]);
    }

    private function collectPortableData(User $user, ?string $orgId = null): array
    {
        // Collect only portable data (not all personal data)
        return [
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at,
            ],
            'orders' => $user->orders()->when($orgId, function ($q) use ($orgId) {
                return $q->where('org_id', $orgId);
            })->select(['id', 'order_number', 'total_amount', 'status', 'created_at'])->get()->toArray(),
            'preferences' => $user->notificationPreferences()->select(['channel', 'enabled'])->get()->toArray(),
        ];
    }

    private function generatePortableFile(array $portableData, string $userId): string
    {
        $filename = "gdpr_portable_{$userId}_".now()->format('Y-m-d_H-i-s').'.json';
        $filepath = storage_path('app/gdpr-exports/'.$filename);

        // Ensure directory exists
        if (! file_exists(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }

        file_put_contents($filepath, json_encode($portableData, JSON_PRETTY_PRINT));

        return $filename;
    }

    private function arrayToCsv(array $data): string
    {
        if (empty($data)) {
            return '';
        }

        $csv = '';
        $headers = array_keys($data[0]);
        $csv .= implode(',', $headers)."\n";

        foreach ($data as $row) {
            $csvRow = [];
            foreach ($headers as $header) {
                $value = $row[$header] ?? '';
                $csvRow[] = is_array($value) ? json_encode($value) : $value;
            }
            $csv .= implode(',', $csvRow)."\n";
        }

        return $csv;
    }

    private function getLogFiles(?string $orgId = null): array
    {
        $logPath = storage_path('logs');
        $files = glob($logPath.'/*.log');

        if ($orgId) {
            // Filter by organization if needed
            $files = array_filter($files, function ($file) use ($orgId) {
                return strpos($file, $orgId) !== false;
            });
        }

        return $files;
    }

    private function getExpiredUserCount(?string $orgId = null): int
    {
        $cutoffDate = now()->subYears(7);

        $query = User::where('created_at', '<', $cutoffDate);
        if ($orgId) {
            $query->whereHas('organizations', function ($q) use ($orgId) {
                $q->where('organizations.id', $orgId);
            });
        }

        return $query->count();
    }

    private function getExpiredOrderCount(?string $orgId = null): int
    {
        $cutoffDate = now()->subYears(5);

        $query = \App\Models\Order::where('created_at', '<', $cutoffDate);
        if ($orgId) {
            $query->where('org_id', $orgId);
        }

        return $query->count();
    }

    private function getLogFileCount(?string $orgId = null): int
    {
        return count($this->getLogFiles($orgId));
    }

    private function getExpiredLogCount(?string $orgId = null): int
    {
        $cutoffDate = now()->subYear();
        $expiredCount = 0;

        foreach ($this->getLogFiles($orgId) as $file) {
            if (filemtime($file) < $cutoffDate->timestamp) {
                $expiredCount++;
            }
        }

        return $expiredCount;
    }

    private function getNextCleanupDate(string $type): string
    {
        return match ($type) {
            'users' => now()->addMonth()->format('Y-m-d'),
            'orders' => now()->addWeek()->format('Y-m-d'),
            'logs' => now()->addDay()->format('Y-m-d'),
            default => now()->addMonth()->format('Y-m-d')
        };
    }
}
