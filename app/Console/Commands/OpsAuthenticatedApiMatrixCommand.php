<?php

namespace App\Console\Commands;

use App\Domain\Exceptions\Models\OperationException;
use App\Domain\Operations\Models\Executor;
use App\Domain\Operations\Models\ServiceJob;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class OpsAuthenticatedApiMatrixCommand extends Command
{
    protected $signature = 'ops:authenticated-api-matrix
        {--emails= : Comma-separated user emails for matrix run}
        {--base-url= : Base URL used for API requests (defaults to APP_URL)}
        {--insecure : Disable TLS certificate verification for HTTPS probes}
        {--json= : Optional JSON report path}';

    protected $description = 'Run authenticated Sanctum API matrix for Ops endpoints (read + safe write probes)';

    public function handle(): int
    {
        $baseUrl = trim((string) $this->option('base-url'));
        if ($baseUrl === '') {
            $baseUrl = trim((string) config('app.url', 'http://127.0.0.1'));
        }
        if ($baseUrl === '') {
            $baseUrl = 'http://127.0.0.1';
        }
        $baseUrl = rtrim($baseUrl, '/');
        $insecure = (bool) $this->option('insecure');
        $emails = $this->resolveEmails();
        $users = User::query()
            ->whereIn('email', $emails)
            ->get()
            ->keyBy(fn (User $user): string => Str::lower((string) $user->email));

        $results = [];

        foreach ($emails as $email) {
            $key = Str::lower($email);
            $user = $users->get($key);

            if (! $user) {
                $results[] = [
                    'email' => $email,
                    'status' => 'missing_user',
                    'roles' => [],
                    'detail' => [],
                ];
                $this->warn("MISSING USER: {$email}");
                continue;
            }

            $token = $user->createToken('ops-auth-matrix-'.time().'-'.Str::random(6))->plainTextToken;

            try {
                $result = $this->runUserMatrix($user, $token, $baseUrl, $insecure);
            } catch (\Throwable $e) {
                $result = [
                    'email' => (string) $user->email,
                    'status' => 'fail',
                    'roles' => $this->extractRoleNames($user),
                    'statuses' => [],
                    'detail' => [
                        'exception' => $e->getMessage(),
                    ],
                ];
            } finally {
                $user->tokens()->latest('id')->limit(1)->delete();
            }

            $results[] = $result;
            $label = strtoupper((string) $result['status']);
            $this->line("{$label}: {$email}");
        }

        $summary = [
            'total' => count($results),
            'ok' => collect($results)->where('status', 'ok')->count(),
            'warn' => collect($results)->where('status', 'warn')->count(),
            'fail' => collect($results)->where('status', 'fail')->count(),
            'missing_user' => collect($results)->where('status', 'missing_user')->count(),
        ];

        $report = [
            'generated_at' => now()->toIso8601String(),
            'base_url' => $baseUrl,
            'insecure' => $insecure,
            'emails' => $emails,
            'summary' => $summary,
            'results' => $results,
        ];

        $jsonPath = (string) ($this->option('json') ?: storage_path('app/ops-authenticated-api-matrix-report.json'));
        File::ensureDirectoryExists(dirname($jsonPath));
        File::put($jsonPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $this->newLine();
        $this->line('Ops authenticated matrix summary:');
        $this->line('Total: '.$summary['total']);
        $this->line('OK: '.$summary['ok']);
        $this->line('WARN: '.$summary['warn']);
        $this->line('FAIL: '.$summary['fail']);
        $this->line('MISSING USER: '.$summary['missing_user']);
        $this->line('Report: '.$jsonPath);

        return $summary['fail'] === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @return array<int, string>
     */
    private function resolveEmails(): array
    {
        $raw = trim((string) $this->option('emails'));
        if ($raw === '') {
            $raw = implode(',', [
                'keks@glf.no',
                'keks@gfl.no',
                'oleksandr@glf.no',
                'maria@glf.no',
                'eva.nystad@glf.no',
            ]);
        }

        return collect(explode(',', $raw))
            ->map(fn (string $email): string => Str::lower(trim($email)))
            ->filter(fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function runUserMatrix(User $user, string $token, string $baseUrl, bool $insecure): array
    {
        $request = function () use ($token, $insecure) {
            $pending = Http::acceptJson()->withToken($token)->timeout(20);
            if ($insecure) {
                $pending = $pending->withoutVerifying();
            }

            return $pending;
        };
        $statuses = [];
        $detail = [];

        $readEndpoints = [
            'map_live' => '/api/ops/map/live',
            'jobs' => '/api/ops/jobs',
            'executors' => '/api/ops/executors',
            'exceptions' => '/api/ops/exceptions',
            'triage' => '/api/ops/workbench/triage',
            'saved_filters_get' => '/api/ops/workbench/saved-filters',
            'replan_recommendations' => '/api/ops/workbench/replan-recommendations',
            'routing_shadow_metrics' => '/api/ops/workbench/routing-shadow-metrics?days=3',
            'routing_provider_health' => '/api/ops/workbench/routing-provider-health',
        ];

        foreach ($readEndpoints as $name => $path) {
            $response = $request()->get($baseUrl.$path);
            $statuses[$name] = $response->status();
        }

        $organizationId = $this->resolveProbeOrganizationId($user) ?? '';
        $jobId = null;
        if ($organizationId !== '') {
            $jobId = ServiceJob::query()
                ->where('organization_id', $organizationId)
                ->latest('id')
                ->value('id');
        }

        if ($jobId) {
            $drawer = $request()->get("{$baseUrl}/api/ops/jobs/{$jobId}/drawer");
            $compare = $request()->get("{$baseUrl}/api/ops/jobs/{$jobId}/candidate-compare");
            $statuses['job_drawer'] = $drawer->status();
            $statuses['candidate_compare'] = $compare->status();
            $detail['job_id'] = $jobId;
            $detail['drawer_has_candidates'] = is_array($drawer->json('dispatch_candidates'));
            $detail['compare_has_payload'] = is_array($compare->json()) || $compare->status() === 403;
        } else {
            $statuses['job_drawer'] = null;
            $statuses['candidate_compare'] = null;
            $detail['job_id'] = null;
        }

        $filterName = 'auth-matrix-'.Str::lower(Str::random(8));
        $filterResponse = $request()->post("{$baseUrl}/api/ops/workbench/saved-filters", [
            'name' => $filterName,
            'filters' => ['domain' => 'delivery', 'at_risk_only' => true],
        ]);
        $statuses['saved_filters_post'] = $filterResponse->status();

        $savedFilterId = $filterResponse->json('id');
        if (is_numeric($savedFilterId)) {
            $deleteResponse = $request()->delete("{$baseUrl}/api/ops/workbench/saved-filters/{$savedFilterId}");
            $statuses['saved_filters_delete'] = $deleteResponse->status();
        } else {
            $statuses['saved_filters_delete'] = null;
        }

        $bulkResponse = $request()->post("{$baseUrl}/api/ops/workbench/bulk-action", [
            'action' => 'exceptions_bulk_acknowledge',
            'ids' => [99999999],
        ]);
        $statuses['bulk_action'] = $bulkResponse->status();
        $detail['bulk_body'] = $bulkResponse->json();

        $writeProbe = $this->runWorkbenchCriticalWriteProbes(
            user: $user,
            token: $token,
            baseUrl: $baseUrl,
            requestFactory: $request,
        );

        foreach ($writeProbe['statuses'] as $key => $statusCode) {
            $statuses[$key] = $statusCode;
        }
        $detail['write_probe'] = $writeProbe['detail'];

        $authFailStatuses = [0, 302, 401, 419];
        $authFailures = collect($statuses)
            ->filter(fn ($status): bool => is_int($status) && in_array($status, $authFailStatuses, true))
            ->all();
        $serverErrors = collect($statuses)
            ->filter(fn ($status): bool => is_int($status) && $status >= 500)
            ->all();

        $status = 'ok';
        if (! empty($authFailures) || ! empty($serverErrors)) {
            $status = 'fail';
        } elseif (collect($statuses)->contains(fn ($s): bool => is_int($s) && $s === 403)) {
            // 403 is expected for some role-restricted endpoints; keep as warn to inspect matrix.
            $status = 'warn';
        }

        $detail['auth_fail_count'] = count($authFailures);
        $detail['server_error_count'] = count($serverErrors);

        return [
            'email' => (string) $user->email,
            'status' => $status,
            'roles' => $this->extractRoleNames($user),
            'statuses' => $statuses,
            'detail' => $detail,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function extractRoleNames(User $user): array
    {
        if (method_exists($user, 'getRoleNames')) {
            return collect($user->getRoleNames())->map(fn ($x): string => (string) $x)->values()->all();
        }

        if (method_exists($user, 'roles')) {
            return $user->roles()->pluck('name')->map(fn ($x): string => (string) $x)->values()->all();
        }

        return [];
    }

    /**
     * @param \Closure(): \Illuminate\Http\Client\PendingRequest $requestFactory
     * @return array{statuses: array<string, int|null>, detail: array<string, mixed>}
     */
    private function runWorkbenchCriticalWriteProbes(
        User $user,
        string $token,
        string $baseUrl,
        \Closure $requestFactory,
    ): array {
        $statuses = [
            'manual_dispatch_fresh' => null,
            'manual_reassign_fresh' => null,
            'exception_acknowledge_fresh' => null,
            'exception_resolve_fresh' => null,
        ];
        $detail = [
            'mode' => 'not_run',
            'organization_id' => null,
            'tenant_id' => null,
            'job_id' => null,
            'exception_id' => null,
        ];

        $organizationId = $this->resolveProbeOrganizationId($user);
        if ($organizationId === null) {
            $detail['mode'] = 'skipped';
            $detail['reason'] = 'missing_organization_scope';
            return ['statuses' => $statuses, 'detail' => $detail];
        }

        $tenantId = $this->resolveProbeTenantId($organizationId);
        $detail['organization_id'] = $organizationId;
        $detail['tenant_id'] = $tenantId;

        $executorA = $this->createProbeExecutor($organizationId, $tenantId);
        $executorB = $this->createProbeExecutor($organizationId, $tenantId);
        $job = $this->createProbeJob($organizationId, $tenantId);

        $detail['mode'] = 'executed';
        $detail['job_id'] = $job->id;
        $detail['executor_ids'] = [$executorA->id, $executorB->id];

        $manualDispatch = $requestFactory()
            ->withToken($token)
            ->withHeaders([
                'X-Idempotency-Key' => 'auth-matrix-dispatch-'.Str::uuid()->toString(),
            ])
            ->post("{$baseUrl}/api/ops/jobs/{$job->id}/manual-dispatch", [
                'executor_id' => $executorA->id,
                'expected_job_version' => $this->formatDrawerVersion($job->updated_at),
                'notes' => 'auth matrix write probe',
            ]);
        $statuses['manual_dispatch_fresh'] = $manualDispatch->status();
        $detail['manual_dispatch_body'] = $manualDispatch->json();

        $job->refresh();
        $manualReassign = $requestFactory()
            ->withToken($token)
            ->withHeaders([
                'X-Idempotency-Key' => 'auth-matrix-reassign-'.Str::uuid()->toString(),
            ])
            ->post("{$baseUrl}/api/ops/jobs/{$job->id}/manual-reassign", [
                'executor_id' => $executorB->id,
                'reason' => 'auth matrix write probe',
                'expected_job_version' => $this->formatDrawerVersion($job->updated_at),
            ]);
        $statuses['manual_reassign_fresh'] = $manualReassign->status();
        $detail['manual_reassign_body'] = $manualReassign->json();

        $exception = $this->createProbeException($organizationId, $tenantId, $job->id, $executorB->id);
        $detail['exception_id'] = $exception->id;

        $ackResponse = $requestFactory()
            ->withToken($token)
            ->withHeaders([
                'X-Idempotency-Key' => 'auth-matrix-ex-ack-'.Str::uuid()->toString(),
            ])
            ->post("{$baseUrl}/api/ops/exceptions/{$exception->id}/acknowledge", [
                'expected_exception_version' => $this->formatDrawerVersion($exception->updated_at),
            ]);
        $statuses['exception_acknowledge_fresh'] = $ackResponse->status();
        $detail['exception_acknowledge_body'] = $ackResponse->json();

        $exception->refresh();
        $resolveResponse = $requestFactory()
            ->withToken($token)
            ->withHeaders([
                'X-Idempotency-Key' => 'auth-matrix-ex-resolve-'.Str::uuid()->toString(),
            ])
            ->post("{$baseUrl}/api/ops/exceptions/{$exception->id}/resolve-workbench", [
                'resolution_code' => 'auth_matrix_probe',
                'resolution_notes' => 'auth matrix write probe',
                'root_cause' => 'smoke_probe',
                'expected_exception_version' => $this->formatDrawerVersion($exception->updated_at),
            ]);
        $statuses['exception_resolve_fresh'] = $resolveResponse->status();
        $detail['exception_resolve_body'] = $resolveResponse->json();

        return ['statuses' => $statuses, 'detail' => $detail];
    }

    private function resolveProbeOrganizationId(User $user): ?string
    {
        foreach ([$user->organization_id ?? null, $user->default_org_id ?? null] as $candidate) {
            $value = trim((string) $candidate);
            if ($value !== '') {
                return $value;
            }
        }

        if ($user->hasRole('admin')) {
            $jobOrg = trim((string) (ServiceJob::query()
                ->whereNotNull('organization_id')
                ->latest('id')
                ->value('organization_id') ?? ''));
            if ($jobOrg !== '') {
                return $jobOrg;
            }

            $executorOrg = trim((string) (Executor::query()
                ->whereNotNull('organization_id')
                ->latest('id')
                ->value('organization_id') ?? ''));
            if ($executorOrg !== '') {
                return $executorOrg;
            }
        }

        return null;
    }

    private function resolveProbeTenantId(string $organizationId): int
    {
        $tenant = ServiceJob::query()
            ->where('organization_id', $organizationId)
            ->whereNotNull('tenant_id')
            ->latest('id')
            ->value('tenant_id');

        if (is_numeric($tenant)) {
            return (int) $tenant;
        }

        $tenant = Executor::query()
            ->where('organization_id', $organizationId)
            ->whereNotNull('tenant_id')
            ->latest('id')
            ->value('tenant_id');

        if (is_numeric($tenant)) {
            return (int) $tenant;
        }

        return 1;
    }

    private function createProbeExecutor(string $organizationId, int $tenantId): Executor
    {
        return Executor::query()->create([
            'organization_id' => $organizationId,
            'tenant_id' => $tenantId,
            'name' => 'Auth Matrix Executor '.Str::upper(Str::random(5)),
            'display_name' => 'Auth Matrix '.Str::upper(Str::random(3)),
            'executor_type' => 'employee',
            'status' => 'available',
            'is_dispatchable' => true,
            'max_concurrent_jobs' => 10,
            'skills' => [],
            'capabilities' => [],
            'capacity' => [],
            'equipment' => [],
            'last_seen_at' => now(),
            'metadata' => ['auth_matrix_probe' => true],
        ]);
    }

    private function createProbeJob(string $organizationId, int $tenantId): ServiceJob
    {
        return ServiceJob::query()->create([
            'organization_id' => $organizationId,
            'tenant_id' => $tenantId,
            'source_type' => 'auth_matrix_probe',
            'service_domain' => 'delivery',
            'job_kind' => 'auth_matrix_probe',
            'status' => 'pending_dispatch',
            'priority' => 'normal',
            'service_lat' => 68.4385,
            'service_lng' => 17.4273,
            'time_window_start' => now()->subMinutes(1),
            'time_window_end' => now()->addMinutes(45),
            'required_skills' => [],
            'required_capacity' => [],
            'required_equipment' => [],
            'metadata' => ['auth_matrix_probe' => true],
        ]);
    }

    private function createProbeException(
        string $organizationId,
        int $tenantId,
        int $jobId,
        int $executorId,
    ): OperationException {
        return OperationException::query()->create([
            'organization_id' => $organizationId,
            'tenant_id' => $tenantId,
            'service_job_id' => $jobId,
            'executor_id' => $executorId,
            'exception_type' => 'auth_matrix_probe',
            'type' => 'auth_matrix_probe',
            'severity' => 'medium',
            'status' => 'open',
            'detected_by' => 'system',
            'detected_at' => now(),
            'summary' => 'Auth matrix probe exception',
            'payload' => ['probe' => true],
            'metadata' => ['auth_matrix_probe' => true],
        ]);
    }

    private function formatDrawerVersion($value): string
    {
        if ($value instanceof Carbon) {
            return $value->format('Y-m-d H:i:s.u');
        }

        if ($value === null) {
            return now()->format('Y-m-d H:i:s.u');
        }

        return Carbon::parse((string) $value)->format('Y-m-d H:i:s.u');
    }
}
