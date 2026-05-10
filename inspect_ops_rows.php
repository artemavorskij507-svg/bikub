<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$jobs = App\Domain\Operations\Models\ServiceJob::query()->orderByDesc('id')->limit(10)->get(['id','organization_id','service_domain','status','priority','executor_id','assignment_id','required_team_size','updated_at']);
foreach ($jobs as $j) {
  echo "JOB {$j->id} org={$j->organization_id} domain={$j->service_domain} status={$j->status} pr={$j->priority} exec={$j->executor_id} asg={$j->assignment_id}\n";
}

$cands = App\Domain\Dispatch\Models\DispatchCandidate::query()->orderByDesc('id')->limit(20)->get(['id','service_job_id','executor_id','is_eligible','selected','rejection_reason','score_total','dispatch_run_id']);
foreach ($cands as $c) {
  echo "CAND {$c->id} job={$c->service_job_id} ex={$c->executor_id} elig={$c->is_eligible} sel={$c->selected} reason={$c->rejection_reason} score={$c->score_total} run={$c->dispatch_run_id}\n";
}

$ex = App\Domain\Exceptions\Models\OperationException::query()->orderByDesc('id')->limit(10)->get(['id','organization_id','service_job_id','type','status','severity']);
foreach ($ex as $e) {
  echo "EX {$e->id} org={$e->organization_id} job={$e->service_job_id} type={$e->type} status={$e->status} sev={$e->severity}\n";
}
