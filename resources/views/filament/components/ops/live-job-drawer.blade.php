@props([
    'job' => null,
    'executor' => null,
    'assignment' => null,
    'sla' => [],
    'exceptions' => [],
    'timeline' => [],
    'dispatchCandidates' => [],
    'runtime' => [],
    'specialHints' => [],
    'diagnostics' => [],
])

<div class="space-y-3 text-sm">
    <div class="font-semibold">Job #{{ data_get($job, 'id', '-') }}</div>
    <div>{{ data_get($job, 'domain', '-') }} | {{ data_get($job, 'kind', '-') }} | {{ data_get($job, 'status_label', '-') }}</div>
    <div>SLA: {{ data_get($job, 'sla_label', '-') }} | Exceptions: {{ data_get($job, 'exceptions_count', 0) }}</div>
    <div>Executor: {{ data_get($executor, 'display_name', 'Unassigned') }}</div>

    @include('filament.components.ops.partials.candidate-diagnostics', [
        'dispatchCandidates' => $dispatchCandidates,
    ])

    @include('filament.components.ops.partials.effective-runtime-rules', [
        'runtime' => $runtime,
    ])
</div>

