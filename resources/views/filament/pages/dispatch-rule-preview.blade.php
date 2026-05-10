<x-filament::page>
    <div class="space-y-4">
        <x-filament::card>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <select wire:model="serviceDomain" class="block w-full rounded-lg border-gray-300">
                    <option value="">All domains</option>
                    <option value="delivery">Delivery</option>
                    <option value="handyman">Handyman</option>
                    <option value="moving">Moving</option>
                    <option value="roadside">Roadside</option>
                    <option value="social_care">Social Care</option>
                </select>

                <select wire:model="jobId" class="block w-full rounded-lg border-gray-300">
                    <option value="">Select job</option>
                    @foreach($this->jobs as $job)
                        <option value="{{ $job->id }}">#{{ $job->id }} | {{ $job->service_domain }} | {{ $job->job_kind ?: '-' }} | {{ $job->status }}</option>
                    @endforeach
                </select>

                <select wire:model="ruleKey" class="block w-full rounded-lg border-gray-300">
                    <option value="">Select rule key</option>
                    @foreach($this->ruleOptions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>

                <input wire:model="ruleValue" type="text" placeholder="Rule value" class="block w-full rounded-lg border-gray-300" />
            </div>
            <div class="mt-3">
                <x-filament::button wire:click="runPreview">Run Preview</x-filament::button>
            </div>
            @if(!empty($errors))
                <div class="mt-3 text-sm text-danger-600">
                    @foreach($errors as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif
        </x-filament::card>

        @if(!empty($ruleInsight))
            <x-filament::card>
                <div class="font-semibold mb-3">Default vs Override</div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3 text-sm">
                    <div><span class="text-gray-500">Default:</span> {{ $ruleInsight['default_value'] ?? '-' }}</div>
                    <div><span class="text-gray-500">Override:</span> {{ $ruleInsight['override_value'] ?? '-' }}</div>
                    <div><span class="text-gray-500">Effective runtime:</span> {{ $ruleInsight['effective_runtime_value'] ?? '-' }}</div>
                    <div>
                        <span class="text-gray-500">Impact:</span>
                        {{ $ruleInsight['impact_label'] ?? 'Normal' }}
                        @if(isset($ruleInsight['delta_percent']) && $ruleInsight['delta_percent'] !== null)
                            ({{ $ruleInsight['delta_percent'] }}%)
                        @endif
                    </div>
                </div>
            </x-filament::card>
        @endif

        <x-filament::card>
            <div class="font-semibold mb-3">Candidate preview</div>
            <div class="overflow-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left border-b">
                            <th class="py-2">Executor</th>
                            <th class="py-2">Eligible</th>
                            <th class="py-2">Rejection reason</th>
                            <th class="py-2">Old score</th>
                            <th class="py-2">New score</th>
                            <th class="py-2">Delta</th>
                            <th class="py-2">Base score</th>
                            <th class="py-2">Weighted score</th>
                            <th class="py-2">Applied modifiers</th>
                            <th class="py-2">Selected</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($previewRows as $row)
                            <tr class="border-b">
                                <td class="py-2">{{ $row['executor_name'] }} (#{{ $row['executor_id'] }})</td>
                                <td class="py-2">{{ $row['eligible'] ? 'yes' : 'no' }}</td>
                                <td class="py-2">{{ $row['rejection_reason'] ?: '-' }}</td>
                                <td class="py-2">{{ $row['old_score'] }}</td>
                                <td class="py-2">{{ $row['new_score'] }}</td>
                                <td class="py-2">{{ $row['delta'] }}</td>
                                <td class="py-2">{{ data_get($row, 'base_score', '-') }}</td>
                                <td class="py-2">{{ data_get($row, 'weighted_score', '-') }}</td>
                                <td class="py-2">
                                    <div class="space-y-1">
                                        <div>Shift: {{ data_get($row, 'shift_fit.eligible') ? 'ok' : 'fail' }}</div>
                                        <div>Window: {{ data_get($row, 'time_window_fit.fits') ? 'ok' : 'fail' }}</div>
                                        <div>Capacity: {{ data_get($row, 'capacity_fit.fits') ? 'ok' : 'fail' }}</div>
                                        <div>Domain: {{ data_get($row, 'modifiers.domain_priority.modifier', 0) }}</div>
                                        <div>Load: {{ data_get($row, 'modifiers.load_modifier.modifier', 0) }}</div>
                                        <div>Emergency: {{ data_get($row, 'modifiers.roadside_emergency_override.modifier', 0) }}</div>
                                    </div>
                                </td>
                                <td class="py-2">{{ $row['selected'] ? 'selected' : '-' }}</td>
                            </tr>
                        @empty
                            <tr><td class="py-3 text-gray-500" colspan="10">No preview yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::card>
    </div>
</x-filament::page>
