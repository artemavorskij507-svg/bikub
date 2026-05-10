<x-filament::page>
    <div class="space-y-6">
        <x-filament::card>
            <div class="text-sm text-gray-500 mb-2">Live Summary</div>
            <div class="grid grid-cols-2 md:grid-cols-6 gap-3 text-sm">
                <div>Total jobs: <strong>{{ data_get($state, 'summary.total_jobs', 0) }}</strong></div>
                <div>Pending: <strong>{{ data_get($state, 'summary.pending_jobs', 0) }}</strong></div>
                <div>Assigned: <strong>{{ data_get($state, 'summary.assigned_jobs', 0) }}</strong></div>
                <div>Started: <strong>{{ data_get($state, 'summary.started_jobs', 0) }}</strong></div>
                <div>SLA risk: <strong>{{ data_get($state, 'summary.sla_warning_jobs', 0) }}</strong></div>
                <div>Open exceptions: <strong>{{ data_get($state, 'summary.open_exceptions', 0) }}</strong></div>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="text-sm text-gray-500 mb-3">Executors (live layer)</div>
            <div class="overflow-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left border-b">
                            <th class="py-2 pr-2">Executor</th>
                            <th class="py-2 pr-2">Status</th>
                            <th class="py-2 pr-2">Type</th>
                            <th class="py-2 pr-2">Live Location</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(data_get($state, 'executors', []) as $executor)
                            <tr class="border-b">
                                <td class="py-2 pr-2">#{{ $executor['id'] }} {{ $executor['name'] }}</td>
                                <td class="py-2 pr-2">{{ $executor['status'] }}</td>
                                <td class="py-2 pr-2">{{ $executor['type'] }}</td>
                                <td class="py-2 pr-2">
                                    @if(!empty($executor['live_location']))
                                        {{ $executor['live_location']['lat'] ?? '-' }}, {{ $executor['live_location']['lng'] ?? '-' }}
                                    @else
                                        n/a
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::card>
    </div>
</x-filament::page>

