@props([
    'dispatchCandidates' => [],
])

<div class="space-y-2">
    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Candidate Diagnostics</div>

    @if(empty($dispatchCandidates))
        <div class="text-xs text-gray-500">No dispatch candidates yet.</div>
    @else
        <div class="space-y-2">
            @foreach($dispatchCandidates as $candidate)
                <div class="rounded border p-2">
                    <div class="flex items-center justify-between">
                        <div class="font-medium">{{ data_get($candidate, 'executor_name', data_get($candidate, 'display_name', '-')) }}</div>
                        <div class="text-xs">
                            @if(data_get($candidate, 'selected'))
                                <span class="rounded bg-green-100 px-2 py-0.5 text-green-700">Selected</span>
                            @endif
                            @if(!data_get($candidate, 'is_eligible', data_get($candidate, 'eligible', false)))
                                <span class="rounded bg-red-100 px-2 py-0.5 text-red-700">Ineligible</span>
                            @else
                                <span class="rounded bg-blue-100 px-2 py-0.5 text-blue-700">Eligible</span>
                            @endif
                        </div>
                    </div>
                    <div class="mt-1 text-xs text-gray-600">
                        Score: {{ data_get($candidate, 'score_total', data_get($candidate, 'score', 0)) }}
                        | ETA: {{ data_get($candidate, 'eta_seconds') ? data_get($candidate, 'eta_seconds').'s' : 'n/a' }}
                        | Distance: {{ data_get($candidate, 'distance_meters') ? data_get($candidate, 'distance_meters').'m' : 'n/a' }}
                    </div>
                    @if(data_get($candidate, 'rejection_reason_label'))
                        <div class="mt-1 text-xs text-red-600">Reason: {{ data_get($candidate, 'rejection_reason_label') }}</div>
                    @endif

                    @include('filament.components.ops.partials.candidate-checks', [
                        'candidate' => $candidate,
                    ])
                    @include('filament.components.ops.partials.candidate-modifiers', [
                        'candidate' => $candidate,
                    ])
                </div>
            @endforeach
        </div>
    @endif
</div>

