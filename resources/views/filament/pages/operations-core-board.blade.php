<x-filament::page>
    <div class="space-y-6">
        <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
            <x-filament::card><div class="text-xs text-gray-500">Total</div><div class="text-2xl font-bold">{{ $summary['total'] ?? 0 }}</div></x-filament::card>
            <x-filament::card><div class="text-xs text-gray-500">Pending</div><div class="text-2xl font-bold">{{ $summary['pending'] ?? 0 }}</div></x-filament::card>
            <x-filament::card><div class="text-xs text-gray-500">Assigned</div><div class="text-2xl font-bold">{{ $summary['assigned'] ?? 0 }}</div></x-filament::card>
            <x-filament::card><div class="text-xs text-gray-500">Started</div><div class="text-2xl font-bold">{{ $summary['started'] ?? 0 }}</div></x-filament::card>
            <x-filament::card><div class="text-xs text-gray-500">Completed</div><div class="text-2xl font-bold">{{ $summary['completed'] ?? 0 }}</div></x-filament::card>
            <x-filament::card><div class="text-xs text-gray-500">SLA Risk</div><div class="text-2xl font-bold text-danger-600">{{ $summary['sla_risk'] ?? 0 }}</div></x-filament::card>
        </div>

        <x-filament::card>
            <div class="overflow-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left border-b">
                            <th class="py-2 pr-2">Job</th>
                            <th class="py-2 pr-2">Domain</th>
                            <th class="py-2 pr-2">Status</th>
                            <th class="py-2 pr-2">Priority</th>
                            <th class="py-2 pr-2">Executor</th>
                            <th class="py-2 pr-2">SLA</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($jobs ?? [] as $job)
                            <tr class="border-b">
                                <td class="py-2 pr-2">#{{ $job->id }}</td>
                                <td class="py-2 pr-2">{{ $job->service_domain }}</td>
                                <td class="py-2 pr-2">{{ $job->status }}</td>
                                <td class="py-2 pr-2">{{ $job->priority }}</td>
                                <td class="py-2 pr-2">{{ $job->activeAssignment?->executor?->name ?? 'Unassigned' }}</td>
                                <td class="py-2 pr-2">
                                    {{ $job->slaTimer?->completion_state ?? 'n/a' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::card>
    </div>
</x-filament::page>

