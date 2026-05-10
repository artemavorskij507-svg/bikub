<?php

namespace App\Console\Commands;

use App\Domain\Dispatch\Actions\ApplyDispatchRuleOverridesAction;
use App\Domain\Dispatch\Actions\BuildDomainAwareDispatchScoreAction;
use App\Domain\Dispatch\Actions\CheckCapacityFitAction;
use App\Domain\Dispatch\Actions\CheckExecutorShiftEligibilityAction;
use App\Domain\Dispatch\Actions\CheckTimeWindowFitAction;
use App\Domain\Dispatch\Actions\ComputeDomainPriorityModifierAction;
use App\Domain\Dispatch\Actions\ComputeLoadModifierAction;
use App\Domain\Dispatch\Actions\LoadDispatchRuleValuesAction;
use App\Domain\Dispatch\Actions\ResolveDispatchRuleSetAction;
use App\Domain\Dispatch\Actions\ResolveRuntimeDispatchRuleSetAction;
use App\Domain\Dispatch\Models\Assignment;
use App\Domain\Dispatch\Models\DispatchRuleSet;
use App\Domain\Dispatch\Models\ExecutorShift;
use App\Domain\Exceptions\Actions\OpenOperationExceptionAction;
use App\Domain\Exceptions\Models\OperationException;
use App\Domain\Moving\Actions\BuildMovingTeamCandidatesAction;
use App\Domain\Moving\Actions\ComputeMovingTeamEtaAction;
use App\Domain\Moving\Actions\CreateTeamAssignmentAction;
use App\Domain\Moving\Models\TeamAssignment;
use App\Domain\Operations\Actions\UpdateServiceJobStatusAction;
use App\Domain\Operations\Actions\WriteJobTimelineAction;
use App\Domain\Operations\Models\Executor;
use App\Domain\Operations\Models\ServiceJob;
use App\Domain\Ops\Models\WorkbenchIdempotencyKey;
use App\Domain\Roadside\Actions\ApplyEmergencyAcceptanceTimeoutAction;
use App\Domain\Roadside\Actions\ApplyEmergencyPriorityOverrideAction;
use App\Domain\Roadside\Actions\FindNearestCapableEmergencyExecutorAction;
use App\Domain\Roadside\Actions\FindPreemptibleAssignmentsAction;
use App\Domain\Roadside\Actions\PreemptLowPriorityAssignmentAction;
use App\Domain\Routing\Actions\BuildRoutingAwareCandidateDiagnosticsAction;
use App\Domain\Routing\Actions\CheckRoutingProviderHealthAction;
use App\Domain\Routing\Actions\CompareEtaStrategiesAction;
use App\Domain\Routing\Actions\EstimateRoutingEtaAction;
use App\Domain\Routing\Actions\RecommendReplanAction;
use App\Domain\Routing\Actions\StoreRoutingEtaSnapshotAction;
use App\Domain\Routing\Models\RoutingEtaSnapshot;
use App\Jobs\CalculateDispatchCandidatesJob;
use App\Models\Operations\DispatchCandidate;
use App\Models\Operations\DispatchRun;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Throwable;

class OpsSmokeDispatchCommand extends Command
{
    protected $signature = 'ops:smoke-dispatch
        {--json= : Optional JSON report path}
        {--skip-seed : Skip Ops smoke seeding}
        {--mode=auto : auto|tests|runtime}';

    protected $description = 'Run Ops smoke scenarios (tests in CI, runtime assertions in no-dev env)';

    public function handle(): int
    {
        if (! $this->option('skip-seed')) {
            $this->info('Seeding ops smoke scenarios...');
            $seedExit = Artisan::call('db:seed', [
                '--class' => 'Database\\Seeders\\OpsSmokeScenarioSeeder',
                '--force' => true,
            ]);
            if ($seedExit !== 0) {
                $this->error('Failed to seed Ops smoke scenarios.');
                return self::FAILURE;
            }
        }

        $mode = (string) $this->option('mode');
        $canUseArtisanTest = collect(Artisan::all())->has('test');
        $phpunitBin = base_path('vendor/bin/phpunit');
        $canUsePhpunit = File::exists($phpunitBin);

        if ($mode === 'auto') {
            $mode = ($canUseArtisanTest || $canUsePhpunit) ? 'tests' : 'runtime';
        }

        $results = $mode === 'runtime'
            ? $this->runRuntimeScenarios()
            : $this->runTestScenarios($canUseArtisanTest, $canUsePhpunit, $phpunitBin);

        $passed = collect($results)->where('status', 'pass')->count();
        $warnings = collect($results)->where('status', 'warn')->count();
        $failed = collect($results)->where('status', 'fail')->count();
        $skipped = collect($results)->where('status', 'skipped')->count();

        $report = [
            'generated_at' => now()->toIso8601String(),
            'mode' => $mode,
            'summary' => [
                'total' => count($results),
                'passed' => $passed,
                'warnings' => $warnings,
                'failed' => $failed,
                'skipped' => $skipped,
            ],
            'results' => $results,
        ];

        $jsonPath = (string) ($this->option('json') ?: storage_path('app/ops-smoke-dispatch-report.json'));
        File::ensureDirectoryExists(dirname($jsonPath));
        File::put($jsonPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $this->line('');
        $this->line('Ops smoke summary:');
        $this->line("Mode: {$mode}");
        $this->line("Total: {$report['summary']['total']}");
        $this->line("Passed: {$report['summary']['passed']}");
        $this->line("Warnings: {$report['summary']['warnings']}");
        $this->line("Failed: {$report['summary']['failed']}");
        $this->line("Skipped: {$report['summary']['skipped']}");
        $this->line("Report: {$jsonPath}");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function runTestScenarios(bool $canUseArtisanTest, bool $canUsePhpunit, string $phpunitBin): array
    {
        $scenarios = [
            'delivery_shift_eligibility' => 'Tests\\Feature\\Ops\\Dispatch\\DeliveryShiftEligibilityTest',
            'delivery_time_window' => 'Tests\\Feature\\Ops\\Dispatch\\DeliveryTimeWindowTest',
            'handyman_capacity_mismatch' => 'Tests\\Feature\\Ops\\Dispatch\\HandymanCapacityMismatchTest',
            'runtime_rules_override' => 'Tests\\Feature\\Ops\\Dispatch\\RuntimeRulesOverrideTest',
            'roadside_emergency_fast_lane' => 'Tests\\Feature\\Ops\\Dispatch\\RoadsideEmergencyFastLaneTest',
            'roadside_no_capable_executor' => 'Tests\\Feature\\Ops\\Dispatch\\RoadsideNoCapableExecutorTest',
            'moving_team_eta_aggregation' => 'Tests\\Feature\\Ops\\Dispatch\\MovingTeamEtaAggregationTest',
            'idempotency_manual_dispatch' => 'Tests\\Feature\\Ops\\Workbench\\ManualDispatchIdempotencyTest',
            'idempotency_manual_reassign' => 'Tests\\Feature\\Ops\\Workbench\\ManualReassignIdempotencyTest',
            'idempotency_exception_ack' => 'Tests\\Feature\\Ops\\Workbench\\ExceptionAcknowledgeIdempotencyTest',
            'idempotency_exception_resolve' => 'Tests\\Feature\\Ops\\Workbench\\ExceptionResolveIdempotencyTest',
        ];

        $results = [];

        foreach ($scenarios as $name => $filter) {
            $this->line("Running: {$name}");
            $exitCode = 1;
            $status = 'fail';
            $tail = [];

            try {
                if ($canUseArtisanTest) {
                    $exitCode = Artisan::call('test', [
                        '--filter' => $filter,
                        '--colors' => 'never',
                    ]);
                    $tail = $this->outputTail(Artisan::output());
                } elseif ($canUsePhpunit) {
                    $process = new Process([
                        PHP_BINARY,
                        $phpunitBin,
                        '--filter',
                        $filter,
                        '--colors=never',
                    ], base_path());
                    $process->setTimeout(600);
                    $process->run();
                    $exitCode = $process->getExitCode() ?? 1;
                    $tail = $this->outputTail(trim($process->getOutput()."\n".$process->getErrorOutput()));
                } else {
                    $exitCode = 0;
                    $status = 'skipped';
                    $tail = ['No test runner available (artisan test/phpunit missing).'];
                }
            } catch (Throwable $e) {
                $exitCode = 1;
                $tail = ['Smoke execution error: '.$e->getMessage()];
            }

            $finalStatus = $status === 'skipped' ? 'skipped' : ($exitCode === 0 ? 'pass' : 'fail');
            $results[$name] = [
                'status' => $finalStatus,
                'exit_code' => $exitCode,
                'tail' => $tail,
                'filter' => $filter,
            ];

            if ($finalStatus === 'pass') {
                $this->info("PASS: {$name}");
            } elseif ($finalStatus === 'skipped') {
                $this->warn("SKIP: {$name}");
            } else {
                $this->error("FAIL: {$name}");
            }
        }

        return $results;
    }

    private function runRuntimeScenarios(): array
    {
        $results = [];
        $org = (string) Str::uuid();
        $tenant = 1;
        $baseUrl = 'https://127.0.0.1';

        $results['runtime_health_routes'] = $this->runtimeCheck('runtime_health_routes', function () {
            $resp = Http::acceptJson()->withoutVerifying()->get('https://127.0.0.1/api/v1/health');
            return [
                'ok' => $resp->ok(),
                'detail' => ['status' => $resp->status(), 'body' => $resp->json()],
            ];
        });
        $results['runtime_admin_pages_guard'] = $this->runtimeCheck('runtime_admin_pages_guard', function () use ($baseUrl) {
            $paths = [
                '/admin',
                '/admin/login',
                '/admin/service-jobs',
                '/admin/operation-exceptions',
                '/admin/live-operations-map',
            ];

            $statuses = [];
            foreach ($paths as $path) {
                $resp = Http::withoutVerifying()->withOptions(['allow_redirects' => false])->get($baseUrl.$path);
                $statuses[$path] = $resp->status();
            }

            $ok = true;
            foreach ($statuses as $path => $status) {
                if ($path === '/admin/login') {
                    if (! in_array($status, [200, 301, 302], true)) {
                        $ok = false;
                        break;
                    }
                    continue;
                }

                if (! in_array($status, [200, 302], true)) {
                    $ok = false;
                    break;
                }
            }

            return [
                'ok' => $ok,
                'detail' => [
                    'statuses' => $statuses,
                ],
            ];
        });

        $results['runtime_routing_provider_reachable'] = $this->runtimeStatusCheck('runtime_routing_provider_reachable', function () {
            $health = app(CheckRoutingProviderHealthAction::class)->execute();

            if ((bool) ($health['reachable'] ?? false)) {
                return [
                    'status' => 'pass',
                    'detail' => $health,
                ];
            }

            return [
                'status' => 'warn',
                'detail' => array_merge($health, [
                    'note' => 'Provider unreachable in shadow mode. Heuristic path remains authoritative.',
                ]),
            ];
        });

        $results['runtime_vipps_readiness'] = $this->runtimeStatusCheck('runtime_vipps_readiness', function () {
            $required = [
                'VIPPS_CLIENT_ID' => config('services.vipps.client_id'),
                'VIPPS_CLIENT_SECRET' => config('services.vipps.client_secret'),
                'VIPPS_SUBSCRIPTION_KEY' => config('services.vipps.subscription_key'),
                'VIPPS_MERCHANT_SERIAL_NUMBER' => config('services.vipps.merchant_serial_number'),
            ];

            $missing = collect($required)
                ->filter(fn ($value): bool => blank($value))
                ->keys()
                ->values()
                ->all();

            $probe = Http::acceptJson()
                ->withoutVerifying()
                ->withOptions(['allow_redirects' => false])
                ->post('https://127.0.0.1/api/v1/payments/vipps/init', []);

            $probeBody = $probe->json();
            $probeBody = is_array($probeBody) ? $probeBody : ['raw' => trim($probe->body())];

            if (empty($missing)) {
                return [
                    'status' => 'pass',
                    'detail' => [
                        'configured' => true,
                        'missing_keys' => [],
                        'probe_status' => $probe->status(),
                        'probe_body' => $probeBody,
                    ],
                ];
            }

            return [
                'status' => 'warn',
                'detail' => [
                    'configured' => false,
                    'missing_keys' => $missing,
                    'probe_status' => $probe->status(),
                    'probe_body' => $probeBody,
                    'note' => 'Vipps not configured. Runtime keeps graceful fallback; Stripe/internal path remains available.',
                ],
            ];
        });

        $results['runtime_dispatch_shift'] = $this->runtimeCheck('runtime_dispatch_shift', function () use ($org, $tenant) {
            $executor = $this->makeExecutor($org, $tenant);
            $this->makeShift($executor, now()->subHours(4), now()->subHours(2));
            $job = $this->makeJob($org, $tenant, 'delivery', 'runtime_shift');
            $run = $this->dispatchJob($job);
            $candidate = $this->latestCandidate($job);

            return [
                'ok' => $run->status === 'no_candidate' && ! $candidate->eligible,
                'detail' => [
                    'run_status' => $run->status,
                    'eligible' => $candidate->eligible,
                    'reasons' => $candidate->ineligibility_reasons,
                ],
            ];
        });

        $results['runtime_dispatch_capacity'] = $this->runtimeCheck('runtime_dispatch_capacity', function () use ($org, $tenant) {
            $executor = $this->makeExecutor($org, $tenant, [
                'equipment' => ['drill'],
                'skills' => ['electricity'],
            ]);
            $this->makeShift($executor, now()->subHour(), now()->addHours(3));
            $job = $this->makeJob($org, $tenant, 'handyman', 'runtime_capacity', [
                'required_equipment' => ['pipe_wrench'],
                'required_skills' => ['plumbing'],
            ]);

            $run = $this->dispatchJob($job);
            $candidate = $this->latestCandidate($job);

            return [
                'ok' => $run->status === 'no_candidate' && ! $candidate->eligible,
                'detail' => [
                    'run_status' => $run->status,
                    'reasons' => $candidate->ineligibility_reasons,
                ],
            ];
        });

        $results['runtime_rules_override'] = $this->runtimeCheck('runtime_rules_override', function () use ($org, $tenant) {
            $executor = $this->makeExecutor($org, $tenant);
            $this->makeShift($executor, now()->subHour(), now()->addHours(3));
            $job = $this->makeJob($org, $tenant, 'delivery', 'runtime_rules');
            DispatchRuleSet::query()->create([
                'organization_id' => $org,
                'tenant_id' => (string) $tenant,
                'service_domain' => 'delivery',
                'job_kind' => 'runtime_rules',
                'rule_key' => 'weights.eta',
                'rule_value_json' => ['value' => 0.55],
                'is_active' => true,
            ]);

            $this->dispatchJob($job);
            $candidate = $this->latestCandidate($job);
            $etaWeight = (float) data_get($candidate->score_breakdown, 'weighted.weights.eta', 0.0);

            return [
                'ok' => abs($etaWeight - 0.55) < 0.0001,
                'detail' => ['eta_weight' => $etaWeight],
            ];
        });

        $results['runtime_roadside_fast_lane'] = $this->runtimeCheck('runtime_roadside_fast_lane', function () use ($org, $tenant) {
            $capable = $this->makeExecutor($org, $tenant, [
                'skills' => ['tow'],
                'capabilities' => ['tow'],
            ]);
            $this->makeShift($capable, now()->subHour(), now()->addHours(4));

            $lowJob = $this->makeJob($org, $tenant, 'roadside', 'runtime_low', ['priority' => 'normal', 'status' => 'assigned']);
            $lowAssignment = Assignment::query()->create([
                'organization_id' => $org,
                'tenant_id' => $tenant,
                'service_job_id' => $lowJob->id,
                'executor_id' => $capable->id,
                'status' => 'proposed',
                'assignment_mode' => 'auto_assign',
            ]);
            $lowJob->update(['executor_id' => $capable->id, 'assignment_id' => $lowAssignment->id]);

            $emergency = $this->makeJob($org, $tenant, 'roadside', 'runtime_emergency', [
                'priority' => 'emergency',
                'required_skills' => ['tow'],
            ]);

            $run = $this->dispatchJob($emergency);
            $newAssignment = $this->latestAssignment($emergency);
            $lowAssignment->refresh();

            return [
                'ok' => $run->status === 'completed'
                    && $lowAssignment->status === 'reassigned'
                    && ! empty($newAssignment->acceptance_deadline_at),
                'detail' => [
                    'run_status' => $run->status,
                    'preempted_status' => $lowAssignment->status,
                    'acceptance_timeout_seconds' => $newAssignment->acceptance_timeout_seconds,
                ],
            ];
        });

        $results['runtime_moving_team_eta'] = $this->runtimeCheck('runtime_moving_team_eta', function () use ($org, $tenant) {
            $e1 = $this->makeExecutor($org, $tenant);
            $e2 = $this->makeExecutor($org, $tenant);
            $this->makeShift($e1, now()->subHour(), now()->addHours(4));
            $this->makeShift($e2, now()->subHour(), now()->addHours(4));
            $job = $this->makeJob($org, $tenant, 'moving', 'runtime_moving', ['required_team_size' => 2]);
            $run = $this->dispatchJob($job);

            $team = TeamAssignment::query()->where('service_job_id', $job->id)->latest('id')->first();
            $memberEtas = (array) data_get($team, 'metadata.member_etas', []);
            $maxEta = empty($memberEtas) ? 0 : max(array_map(static fn (array $x): int => (int) ($x['eta_seconds'] ?? 0), $memberEtas));
            $teamEta = $team?->eta_at ? abs(now()->diffInSeconds($team->eta_at, false)) : null;

            return [
                'ok' => $run->status === 'completed' && $team !== null && $maxEta > 0,
                'detail' => [
                    'run_status' => $run->status,
                    'team_id' => $team?->id,
                    'member_etas_count' => count($memberEtas),
                    'max_member_eta' => $maxEta,
                    'team_eta_seconds_from_now' => $teamEta,
                ],
            ];
        });

        $results['runtime_idempotency_api'] = $this->runtimeCheck('runtime_idempotency_api', function () use ($org, $tenant, $baseUrl) {
            $user = $this->makeOpsAdminUser($org);
            $token = $user->createToken('ops-runtime-smoke-'.time())->plainTextToken;

            $executorA = $this->makeExecutor($org, $tenant);
            $executorB = $this->makeExecutor($org, $tenant);
            $this->makeShift($executorA, now()->subHour(), now()->addHours(4));
            $this->makeShift($executorB, now()->subHour(), now()->addHours(4));

            $job = $this->makeJob($org, $tenant, 'delivery', 'runtime_idem');
            $v = optional($job->updated_at)->format('Y-m-d H:i:s.u');
            $key = 'runtime-idem-'.Str::uuid()->toString();

            $url = "{$baseUrl}/api/ops/jobs/{$job->id}/manual-dispatch";
            $first = Http::acceptJson()
                ->withoutVerifying()
                ->withToken($token)
                ->withHeaders(['X-Idempotency-Key' => $key])
                ->post($url, [
                    'executor_id' => $executorA->id,
                    'expected_job_version' => $v,
                    'notes' => 'runtime smoke',
                ]);
            $second = Http::acceptJson()
                ->withoutVerifying()
                ->withToken($token)
                ->withHeaders(['X-Idempotency-Key' => $key])
                ->post($url, [
                    'executor_id' => $executorA->id,
                    'expected_job_version' => $v,
                    'notes' => 'runtime smoke',
                ]);
            $third = Http::acceptJson()
                ->withoutVerifying()
                ->withToken($token)
                ->withHeaders(['X-Idempotency-Key' => $key])
                ->post($url, [
                    'executor_id' => $executorB->id,
                    'expected_job_version' => optional($job->fresh()->updated_at)->format('Y-m-d H:i:s.u'),
                    'notes' => 'different payload',
                ]);

            $assignments = Assignment::query()->where('service_job_id', $job->id)->count();
            $idem = WorkbenchIdempotencyKey::query()->where('idempotency_key', $key)->first();

            return [
                'ok' => $first->status() === 200 && $second->status() === 200 && $third->status() === 409 && $assignments === 1,
                'detail' => [
                    'statuses' => [$first->status(), $second->status(), $third->status()],
                    'assignments_count' => $assignments,
                    'idempotency_state' => $idem?->state,
                ],
            ];
        });

        $results['runtime_authenticated_secured_paths'] = $this->runtimeCheck('runtime_authenticated_secured_paths', function () use ($org, $tenant, $baseUrl) {
            $user = $this->makeOpsAdminUser($org);
            $token = $user->createToken('ops-runtime-secured-'.time())->plainTextToken;

            $executor = $this->makeExecutor($org, $tenant);
            $this->makeShift($executor, now()->subHour(), now()->addHours(4));
            $job = $this->makeJob($org, $tenant, 'delivery', 'runtime_secured');
            $this->dispatchJob($job);

            $mapLive = Http::acceptJson()->withoutVerifying()->withToken($token)->get("{$baseUrl}/api/ops/map/live");
            $drawer = Http::acceptJson()->withoutVerifying()->withToken($token)->get("{$baseUrl}/api/ops/jobs/{$job->id}/drawer");
            $compare = Http::acceptJson()->withoutVerifying()->withToken($token)->get("{$baseUrl}/api/ops/jobs/{$job->id}/candidate-compare");
            $triage = Http::acceptJson()->withoutVerifying()->withToken($token)->get("{$baseUrl}/api/ops/workbench/triage");
            $savedFilters = Http::acceptJson()->withoutVerifying()->withToken($token)->get("{$baseUrl}/api/ops/workbench/saved-filters");
            $replan = Http::acceptJson()->withoutVerifying()->withToken($token)->get("{$baseUrl}/api/ops/workbench/replan-recommendations");
            $routingMetrics = Http::acceptJson()->withoutVerifying()->withToken($token)->get("{$baseUrl}/api/ops/workbench/routing-shadow-metrics?days=3");
            $routingHealth = Http::acceptJson()->withoutVerifying()->withToken($token)->get("{$baseUrl}/api/ops/workbench/routing-provider-health");
            $savedFilterCreate = Http::acceptJson()->withoutVerifying()->withToken($token)->post("{$baseUrl}/api/ops/workbench/saved-filters", [
                'name' => 'runtime-secured-'.Str::lower(Str::random(8)),
                'filters' => ['domain' => 'delivery', 'at_risk_only' => true],
            ]);
            $bulk = Http::acceptJson()->withoutVerifying()->withToken($token)->post("{$baseUrl}/api/ops/workbench/bulk-action", [
                'action' => 'exceptions_bulk_acknowledge',
                'ids' => [99999999],
            ]);

            $statuses = [
                'map_live' => $mapLive->status(),
                'job_drawer' => $drawer->status(),
                'candidate_compare' => $compare->status(),
                'triage' => $triage->status(),
                'saved_filters_get' => $savedFilters->status(),
                'replan_recommendations' => $replan->status(),
                'routing_shadow_metrics' => $routingMetrics->status(),
                'routing_provider_health' => $routingHealth->status(),
                'saved_filters_post' => $savedFilterCreate->status(),
                'bulk_action' => $bulk->status(),
            ];

            $ok = collect($statuses)->every(fn (int $status): bool => $status === 200);

            return [
                'ok' => $ok,
                'detail' => [
                    'statuses' => $statuses,
                    'drawer_has_candidates' => is_array($drawer->json('dispatch_candidates')),
                    'compare_has_payload' => $compare->json('job_id') === $job->id,
                    'compare_has_eta_diff' => is_array($compare->json('eta_strategy_diff')),
                    'replan_payload_shape' => is_array($replan->json('items')),
                    'routing_metrics_shape' => is_array($routingMetrics->json('metrics')),
                    'routing_health_shape' => is_array($routingHealth->json()),
                ],
            ];
        });

        $results['runtime_routing_shadow_mode'] = $this->runtimeCheck('runtime_routing_shadow_mode', function () use ($org, $tenant) {
            $executor = $this->makeExecutor($org, $tenant);
            $this->makeShift($executor, now()->subHour(), now()->addHours(4));
            $job = $this->makeJob($org, $tenant, 'delivery', 'runtime_routing_shadow');
            $run = $this->dispatchJob($job);
            $candidate = $this->latestCandidate($job);
            $routing = (array) data_get($candidate->score_breakdown, 'routing', []);

            return [
                'ok' => in_array($run->status, ['completed', 'no_candidate'], true)
                    && array_key_exists('routing_available', $routing),
                'detail' => [
                    'run_status' => $run->status,
                    'routing_available' => data_get($routing, 'routing_available'),
                    'routing_error' => data_get($routing, 'routing_error'),
                    'snapshots_count' => RoutingEtaSnapshot::query()->where('service_job_id', $job->id)->count(),
                ],
            ];
        });

        return $results;
    }

    private function runtimeCheck(string $name, callable $fn): array
    {
        try {
            $result = $fn();
            $ok = (bool) ($result['ok'] ?? false);

            if ($ok) {
                $this->info("PASS: {$name}");
            } else {
                $this->error("FAIL: {$name}");
            }

            return [
                'status' => $ok ? 'pass' : 'fail',
                'detail' => $result['detail'] ?? null,
            ];
        } catch (Throwable $e) {
            $this->error("FAIL: {$name} ({$e->getMessage()})");
            return [
                'status' => 'fail',
                'detail' => ['exception' => $e->getMessage()],
            ];
        }
    }

    private function runtimeStatusCheck(string $name, callable $fn): array
    {
        try {
            $result = (array) $fn();
            $status = (string) ($result['status'] ?? 'fail');
            $status = in_array($status, ['pass', 'warn', 'fail', 'skipped'], true) ? $status : 'fail';
            $detail = $result['detail'] ?? null;

            if ($status === 'pass') {
                $this->info("PASS: {$name}");
            } elseif ($status === 'warn') {
                $this->warn("WARN: {$name}");
            } elseif ($status === 'skipped') {
                $this->warn("SKIP: {$name}");
            } else {
                $this->error("FAIL: {$name}");
            }

            return [
                'status' => $status,
                'detail' => $detail,
            ];
        } catch (Throwable $e) {
            $this->error("FAIL: {$name} ({$e->getMessage()})");
            return [
                'status' => 'fail',
                'detail' => ['exception' => $e->getMessage()],
            ];
        }
    }

    private function outputTail(string $output): array
    {
        return collect(explode("\n", trim($output)))
            ->filter(static fn (string $line): bool => trim($line) !== '')
            ->take(-6)
            ->values()
            ->all();
    }

    private function makeExecutor(string $organizationId, int $tenantId, array $overrides = []): Executor
    {
        return Executor::query()->create(array_merge([
            'organization_id' => $organizationId,
            'tenant_id' => $tenantId,
            'name' => 'Runtime Executor '.Str::random(6),
            'display_name' => 'Runtime Executor '.Str::random(4),
            'executor_type' => 'employee',
            'status' => 'available',
            'is_dispatchable' => true,
            'max_concurrent_jobs' => 10,
            'skills' => [],
            'capabilities' => [],
            'capacity' => [],
            'equipment' => [],
            'last_seen_at' => now(),
        ], $overrides));
    }

    private function makeShift(Executor $executor, $startsAt, $endsAt): ExecutorShift
    {
        return ExecutorShift::query()->create([
            'organization_id' => $executor->organization_id,
            'tenant_id' => (string) ($executor->tenant_id ?? 1),
            'executor_id' => $executor->id,
            'day_of_week' => (int) now()->dayOfWeekIso,
            'start_time' => $startsAt->format('H:i:s'),
            'end_time' => $endsAt->format('H:i:s'),
            'shift_date' => now()->toDateString(),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'is_active' => true,
            'is_available' => true,
            'timezone' => 'UTC',
        ]);
    }

    private function makeJob(string $organizationId, int $tenantId, string $domain, string $kind, array $overrides = []): ServiceJob
    {
        return ServiceJob::query()->create(array_merge([
            'organization_id' => $organizationId,
            'tenant_id' => $tenantId,
            'source_type' => 'runtime_smoke',
            'service_domain' => $domain,
            'job_kind' => $kind,
            'status' => 'pending_dispatch',
            'priority' => 'normal',
            'service_lat' => 50.4501,
            'service_lng' => 30.5234,
            'time_window_start' => now()->subMinute(),
            'time_window_end' => now()->addMinutes(60),
            'required_skills' => [],
            'required_capacity' => [],
            'required_equipment' => [],
            'metadata' => ['runtime_smoke' => true],
        ], $overrides));
    }

    private function dispatchJob(ServiceJob $job): DispatchRun
    {
        app()->call([new CalculateDispatchCandidatesJob($job->id), 'handle']);

        return DispatchRun::query()->where('service_job_id', $job->id)->latest('id')->firstOrFail();
    }

    private function latestCandidate(ServiceJob $job): DispatchCandidate
    {
        return DispatchCandidate::query()->where('service_job_id', $job->id)->latest('id')->firstOrFail();
    }

    private function latestAssignment(ServiceJob $job): Assignment
    {
        return Assignment::query()->where('service_job_id', $job->id)->latest('id')->firstOrFail();
    }

    private function makeOpsAdminUser(?string $organizationId = null): User
    {
        if ($organizationId !== null && $organizationId !== '') {
            $this->ensureOrganization($organizationId);
        }

        $user = User::query()->create([
            'name' => 'Ops Runtime Smoke',
            'email' => 'ops-runtime-smoke-'.Str::lower(Str::random(8)).'@example.test',
            'password' => Hash::make(Str::random(16)),
            'email_verified_at' => now(),
        ]);

        if ($organizationId !== null && $organizationId !== '') {
            $user->forceFill(['default_org_id' => $organizationId])->save();
        }

        $role = Role::query()->firstOrCreate(
            ['name' => 'admin'],
            [
                'slug' => 'admin',
                'description' => 'Runtime smoke admin',
                'permissions' => ['*'],
                'is_active' => true,
            ]
        );

        DB::table('user_roles')->updateOrInsert(
            ['user_id' => $user->id, 'role_id' => $role->id],
            ['assigned_at' => now(), 'created_at' => now(), 'updated_at' => now()]
        );

        return $user;
    }

    private function ensureOrganization(string $organizationId): void
    {
        Organization::query()->updateOrCreate(
            ['id' => $organizationId],
            [
                'name' => 'Runtime Smoke '.Str::upper(Str::random(4)),
                'slug' => 'runtime-smoke-'.Str::lower(Str::random(8)),
                'status' => 'active',
            ]
        );
    }
}
