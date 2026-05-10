<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Domain\Operations\Models\Executor;
use App\Domain\Operations\Models\ServiceJob;
use App\Domain\Dispatch\Models\Assignment;
use App\Domain\Exceptions\Models\OperationException;
use App\Domain\Ops\Models\WorkbenchIdempotencyKey;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

function drawerVersion($model): string {
    return optional($model->updated_at)->format('Y-m-d H:i:s.u') ?? now()->format('Y-m-d H:i:s.u');
}

function httpJson(string $method, string $url, string $token, array $payload = [], array $headers = []): array {
    $ch = curl_init($url);
    $h = [
        'Accept: application/json',
        'Content-Type: application/json',
        'Authorization: Bearer '.$token,
    ];
    foreach ($headers as $k => $v) { $h[] = $k.': '.$v; }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => $h,
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
        CURLOPT_TIMEOUT => 30,
    ]);

    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    $json = null;
    if (is_string($raw) && strlen($raw) > 0) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) { $json = $decoded; }
    }

    return [
        'status' => $status,
        'json' => $json,
        'raw' => $raw,
        'error' => $err ?: null,
    ];
}

function httpConcurrentSame(string $url, string $token, array $payload, array $headers): array {
    $build = function() use ($url, $token, $payload, $headers) {
        $ch = curl_init($url);
        $h = [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Bearer '.$token,
        ];
        foreach ($headers as $k => $v) { $h[] = $k.': '.$v; }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_HTTPHEADER => $h,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
            CURLOPT_TIMEOUT => 30,
        ]);
        return $ch;
    };

    $mh = curl_multi_init();
    $c1 = $build();
    $c2 = $build();
    curl_multi_add_handle($mh, $c1);
    curl_multi_add_handle($mh, $c2);

    do {
        $status = curl_multi_exec($mh, $running);
        if ($running) { curl_multi_select($mh, 0.1); }
    } while ($running && $status === CURLM_OK);

    $resp1 = curl_multi_getcontent($c1);
    $resp2 = curl_multi_getcontent($c2);
    $s1 = (int) curl_getinfo($c1, CURLINFO_HTTP_CODE);
    $s2 = (int) curl_getinfo($c2, CURLINFO_HTTP_CODE);

    curl_multi_remove_handle($mh, $c1);
    curl_multi_remove_handle($mh, $c2);
    curl_close($c1);
    curl_close($c2);
    curl_multi_close($mh);

    return [
        ['status'=>$s1,'json'=>json_decode($resp1,true),'raw'=>$resp1],
        ['status'=>$s2,'json'=>json_decode($resp2,true),'raw'=>$resp2],
    ];
}

$u = User::where('email','keks@glf.no')->firstOrFail();
$token = $u->createToken('ops-idem-api-'.time())->plainTextToken;
$base = 'http://127.0.0.1/api/ops';

$org = (string) Executor::query()->whereNotNull('organization_id')->value('organization_id');
$tenant = (int) (Executor::query()->where('organization_id', $org)->value('tenant_id') ?? 1);

$createExecutor = function(string $suffix = '') use ($org, $tenant) {
    return Executor::query()->create([
        'organization_id' => $org,
        'tenant_id' => $tenant,
        'name' => 'Idem API '.($suffix ?: Str::random(4)),
        'display_name' => 'Idem API '.($suffix ?: Str::random(4)),
        'executor_type' => 'employee',
        'status' => 'available',
        'is_dispatchable' => true,
        'max_concurrent_jobs' => 20,
        'skills' => [],
        'capabilities' => [],
        'capacity' => [],
        'equipment' => [],
        'last_seen_at' => now(),
    ]);
};

$createJob = function(string $kind='idem-api') use ($org, $tenant) {
    return ServiceJob::query()->create([
        'organization_id' => $org,
        'tenant_id' => $tenant,
        'source_type' => 'smoke',
        'service_domain' => 'delivery',
        'job_kind' => $kind,
        'status' => 'pending_dispatch',
        'priority' => 'normal',
        'service_lat' => 50.4501,
        'service_lng' => 30.5234,
        'time_window_start' => now()->subMinutes(1),
        'time_window_end' => now()->addMinutes(90),
        'required_skills' => [],
        'required_capacity' => [],
        'required_equipment' => [],
        'metadata' => ['idem_api' => true],
    ]);
};

$report = [
    'generated_at' => now()->toIso8601String(),
    'user_id' => $u->id,
    'token_prefix' => substr($token,0,12),
    'scenarios' => [],
];

// Case A: serial replay same key/payload
$e1 = $createExecutor('A1');
$e2 = $createExecutor('A2');
$jobA = $createJob('idem-api-a');
$keyA = 'idem-A-'.Str::uuid()->toString();
$payloadA = ['executor_id' => (int)$e1->id, 'expected_job_version' => drawerVersion($jobA), 'notes' => 'idem A'];
$hA = ['X-Idempotency-Key' => $keyA];
$rA1 = httpJson('POST', "$base/jobs/{$jobA->id}/manual-dispatch", $token, $payloadA, $hA);
$rA2 = httpJson('POST', "$base/jobs/{$jobA->id}/manual-dispatch", $token, $payloadA, $hA);
$rA3 = httpJson('POST', "$base/jobs/{$jobA->id}/manual-dispatch", $token, $payloadA, $hA);
$assignA = Assignment::query()->where('service_job_id',$jobA->id)->count();
$idemA = WorkbenchIdempotencyKey::query()->where('idempotency_key',$keyA)->latest('id')->first();
$report['scenarios']['A_serial_same_key'] = [
    'job_id' => $jobA->id,
    'statuses' => [$rA1['status'],$rA2['status'],$rA3['status']],
    'responses_equal' => (($rA1['raw'] ?? '') === ($rA2['raw'] ?? '') && ($rA2['raw'] ?? '') === ($rA3['raw'] ?? '')),
    'assignments_count' => $assignA,
    'idempotency_state' => $idemA?->state,
    'idempotency_status' => $idemA?->response_status,
    'messages' => [
        $rA1['json']['message'] ?? $rA1['raw'] ?? null,
        $rA2['json']['message'] ?? $rA2['raw'] ?? null,
        $rA3['json']['message'] ?? $rA3['raw'] ?? null,
    ],
];

// Case B: concurrent replay
$eB = $createExecutor('B1');
$jobB = $createJob('idem-api-b');
$keyB = 'idem-B-'.Str::uuid()->toString();
$payloadB = ['executor_id' => (int)$eB->id, 'expected_job_version' => drawerVersion($jobB), 'notes' => 'idem B'];
$resB = httpConcurrentSame("$base/jobs/{$jobB->id}/manual-dispatch", $token, $payloadB, ['X-Idempotency-Key' => $keyB]);
$assignB = Assignment::query()->where('service_job_id',$jobB->id)->count();
$idemB = WorkbenchIdempotencyKey::query()->where('idempotency_key',$keyB)->latest('id')->first();
$report['scenarios']['B_concurrent_same_key'] = [
    'job_id' => $jobB->id,
    'statuses' => [$resB[0]['status'], $resB[1]['status']],
    'assignments_count' => $assignB,
    'idempotency_state' => $idemB?->state,
    'idempotency_status' => $idemB?->response_status,
    'messages' => [
        $resB[0]['json']['message'] ?? $resB[0]['raw'] ?? null,
        $resB[1]['json']['message'] ?? $resB[1]['raw'] ?? null,
    ],
];

// Case C: same key different payload
$eC1 = $createExecutor('C1');
$eC2 = $createExecutor('C2');
$jobC = $createJob('idem-api-c');
$keyC = 'idem-C-'.Str::uuid()->toString();
$payloadC1 = ['executor_id' => (int)$eC1->id, 'expected_job_version' => drawerVersion($jobC), 'notes' => 'idem C1'];
$rC1 = httpJson('POST', "$base/jobs/{$jobC->id}/manual-dispatch", $token, $payloadC1, ['X-Idempotency-Key' => $keyC]);
$jobC->refresh();
$payloadC2 = ['executor_id' => (int)$eC2->id, 'expected_job_version' => drawerVersion($jobC), 'notes' => 'idem C2'];
$rC2 = httpJson('POST', "$base/jobs/{$jobC->id}/manual-dispatch", $token, $payloadC2, ['X-Idempotency-Key' => $keyC]);
$assignC = Assignment::query()->where('service_job_id',$jobC->id)->count();
$report['scenarios']['C_same_key_diff_payload'] = [
    'job_id' => $jobC->id,
    'statuses' => [$rC1['status'],$rC2['status']],
    'second_message' => $rC2['json']['message'] ?? null,
    'assignments_count' => $assignC,
];

// Case D: manual reassign replay
$eD1 = $createExecutor('D1');
$eD2 = $createExecutor('D2');
$jobD = $createJob('idem-api-d');
$baseAssignment = Assignment::query()->create([
    'organization_id' => $org,
    'tenant_id' => $tenant,
    'service_job_id' => $jobD->id,
    'executor_id' => $eD1->id,
    'assignment_mode' => 'auto',
    'status' => 'accepted',
    'accepted_at' => now(),
    'eta_at' => now()->addMinutes(20),
    'metadata' => ['seed' => 'idemD'],
]);
$jobD->update(['status' => 'assigned', 'executor_id' => $eD1->id, 'assignment_id' => $baseAssignment->id]);
$jobD->refresh();
$keyD = 'idem-D-'.Str::uuid()->toString();
$payloadD1 = ['executor_id' => (int)$eD2->id, 'reason' => 'idem D', 'expected_job_version' => drawerVersion($jobD)];
$rD1 = httpJson('POST', "$base/jobs/{$jobD->id}/manual-reassign", $token, $payloadD1, ['X-Idempotency-Key' => $keyD]);
$rD2 = httpJson('POST', "$base/jobs/{$jobD->id}/manual-reassign", $token, $payloadD1, ['X-Idempotency-Key' => $keyD]);
$jobD->refresh();
$payloadD3 = ['executor_id' => (int)$eD1->id, 'reason' => 'idem D other', 'expected_job_version' => drawerVersion($jobD)];
$rD3 = httpJson('POST', "$base/jobs/{$jobD->id}/manual-reassign", $token, $payloadD3, ['X-Idempotency-Key' => $keyD]);
$assignD = Assignment::query()->where('service_job_id',$jobD->id)->count();
$report['scenarios']['D_reassign_replay_and_conflict'] = [
    'job_id' => $jobD->id,
    'statuses' => [$rD1['status'],$rD2['status'],$rD3['status']],
    'assignments_count' => $assignD,
    'third_message' => $rD3['json']['message'] ?? null,
];

// Case E: exception acknowledge replay
$exE = OperationException::query()->create([
    'organization_id' => $org,
    'tenant_id' => $tenant,
    'service_job_id' => $jobD->id,
    'assignment_id' => $jobD->assignment_id,
    'executor_id' => $jobD->executor_id,
    'type' => 'idem_exception_ack',
    'exception_type' => 'idem_exception_ack',
    'severity' => 'medium',
    'status' => 'open',
    'detected_by' => 'smoke',
    'detected_at' => now(),
    'payload' => ['idem' => true],
]);
$keyE = 'idem-E-'.Str::uuid()->toString();
$payloadE = ['expected_exception_version' => drawerVersion($exE)];
$rE1 = httpJson('POST', "$base/exceptions/{$exE->id}/acknowledge", $token, $payloadE, ['X-Idempotency-Key' => $keyE]);
$rE2 = httpJson('POST', "$base/exceptions/{$exE->id}/acknowledge", $token, $payloadE, ['X-Idempotency-Key' => $keyE]);
$report['scenarios']['E_exception_ack_replay'] = [
    'exception_id' => $exE->id,
    'statuses' => [$rE1['status'],$rE2['status']],
    'same_response' => (($rE1['raw'] ?? '') === ($rE2['raw'] ?? '')),
    'status_now' => OperationException::query()->find($exE->id)?->status,
    'messages' => [
        $rE1['json']['message'] ?? $rE1['raw'] ?? null,
        $rE2['json']['message'] ?? $rE2['raw'] ?? null,
    ],
];

// Case F: exception resolve replay + conflict
$exF = OperationException::query()->create([
    'organization_id' => $org,
    'tenant_id' => $tenant,
    'service_job_id' => $jobD->id,
    'assignment_id' => $jobD->assignment_id,
    'executor_id' => $jobD->executor_id,
    'type' => 'idem_exception_resolve',
    'exception_type' => 'idem_exception_resolve',
    'severity' => 'high',
    'status' => 'open',
    'detected_by' => 'smoke',
    'detected_at' => now(),
    'payload' => ['idem' => true],
]);
$keyF = 'idem-F-'.Str::uuid()->toString();
$payloadF1 = [
    'expected_exception_version' => drawerVersion($exF),
    'resolution_code' => 'resolved_by_dispatcher',
    'resolution_notes' => 'idem F',
    'root_cause' => 'test',
];
$rF1 = httpJson('POST', "$base/exceptions/{$exF->id}/resolve-workbench", $token, $payloadF1, ['X-Idempotency-Key' => $keyF]);
$rF2 = httpJson('POST', "$base/exceptions/{$exF->id}/resolve-workbench", $token, $payloadF1, ['X-Idempotency-Key' => $keyF]);
$exF->refresh();
$payloadF3 = $payloadF1;
$payloadF3['resolution_code'] = 'different_code';
$payloadF3['expected_exception_version'] = drawerVersion($exF);
$rF3 = httpJson('POST', "$base/exceptions/{$exF->id}/resolve-workbench", $token, $payloadF3, ['X-Idempotency-Key' => $keyF]);
$report['scenarios']['F_exception_resolve_replay_and_conflict'] = [
    'exception_id' => $exF->id,
    'statuses' => [$rF1['status'],$rF2['status'],$rF3['status']],
    'status_now' => OperationException::query()->find($exF->id)?->status,
    'third_message' => $rF3['json']['message'] ?? null,
    'messages' => [
        $rF1['json']['message'] ?? $rF1['raw'] ?? null,
        $rF2['json']['message'] ?? $rF2['raw'] ?? null,
        $rF3['json']['message'] ?? $rF3['raw'] ?? null,
    ],
];

// DB evidence
$report['evidence'] = [
    'workbench_idempotency_keys' => WorkbenchIdempotencyKey::query()
        ->where('idempotency_key', 'like', 'idem-%')
        ->orderByDesc('id')->limit(50)
        ->get(['id','action_name','idempotency_key','request_hash','state','response_status','target_type','target_id','created_at','updated_at'])
        ->toArray(),
    'assignments' => Assignment::query()
        ->whereIn('service_job_id', [$jobA->id,$jobB->id,$jobC->id,$jobD->id])
        ->orderByDesc('id')->get(['id','service_job_id','executor_id','status','cancel_reason','created_at'])
        ->toArray(),
    'job_timelines' => DB::table('job_timelines')
        ->whereIn('service_job_id', [$jobA->id,$jobB->id,$jobC->id,$jobD->id])
        ->orderByDesc('id')->limit(100)
        ->get(['id','service_job_id','assignment_id','event_type','occurred_at'])
        ->toArray(),
    'operation_exceptions' => OperationException::query()
        ->whereIn('id', [$exE->id,$exF->id])
        ->orderByDesc('id')
        ->get(['id','service_job_id','type','severity','status','resolution_code','updated_at'])
        ->toArray(),
];

if (DB::getSchemaBuilder()->hasTable('workbench_action_audits')) {
    $report['evidence']['workbench_action_audits'] = DB::table('workbench_action_audits')
        ->orderByDesc('id')->limit(50)->get()->toArray();
}

$file = '/tmp/phase11_idempotency_api_report.json';
file_put_contents($file, json_encode($report, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
echo "REPORT={$file}\n";
echo json_encode([
    'A' => $report['scenarios']['A_serial_same_key']['statuses'],
    'B' => $report['scenarios']['B_concurrent_same_key']['statuses'],
    'C' => $report['scenarios']['C_same_key_diff_payload']['statuses'],
    'D' => $report['scenarios']['D_reassign_replay_and_conflict']['statuses'],
    'E' => $report['scenarios']['E_exception_ack_replay']['statuses'],
    'F' => $report['scenarios']['F_exception_resolve_replay_and_conflict']['statuses'],
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";


