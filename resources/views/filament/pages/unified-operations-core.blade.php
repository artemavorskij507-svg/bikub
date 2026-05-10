<x-filament::page>
    <div class="space-y-6">
        <x-filament::card>
            <div class="flex items-center justify-between gap-4">
                <div class="text-sm text-gray-500">Operations snapshot</div>
                <a class="text-primary-600 text-sm" href="{{ \App\Filament\Resources\ServiceJobResource::getUrl('index') }}">Open queue</a>
            </div>
            <div class="mt-4 grid grid-cols-2 md:grid-cols-4 xl:grid-cols-8 gap-3">
                <x-filament::card><div class="text-xs text-gray-500">Active jobs</div><div class="text-2xl font-bold">{{ data_get($state,'kpi.active_jobs',0) }}</div></x-filament::card>
                <x-filament::card><div class="text-xs text-gray-500">Pending dispatch</div><div class="text-2xl font-bold">{{ data_get($state,'kpi.pending_dispatch',0) }}</div></x-filament::card>
                <x-filament::card><div class="text-xs text-gray-500">Assigned</div><div class="text-2xl font-bold">{{ data_get($state,'kpi.assigned',0) }}</div></x-filament::card>
                <x-filament::card><div class="text-xs text-gray-500">In progress</div><div class="text-2xl font-bold">{{ data_get($state,'kpi.in_progress',0) }}</div></x-filament::card>
                <x-filament::card><div class="text-xs text-gray-500">At risk</div><div class="text-2xl font-bold text-warning-600">{{ data_get($state,'kpi.at_risk',0) }}</div></x-filament::card>
                <x-filament::card><div class="text-xs text-gray-500">Open exceptions</div><div class="text-2xl font-bold text-danger-600">{{ data_get($state,'kpi.open_exceptions',0) }}</div></x-filament::card>
                <x-filament::card><div class="text-xs text-gray-500">Avg dispatch</div><div class="text-2xl font-bold">{{ data_get($state,'kpi.avg_dispatch_time',0) }}m</div></x-filament::card>
                <x-filament::card><div class="text-xs text-gray-500">Avg arrival delay</div><div class="text-2xl font-bold">{{ data_get($state,'kpi.avg_arrival_delay',0) }}m</div></x-filament::card>
            </div>
        </x-filament::card>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <x-filament::card>
                <div class="font-semibold mb-3">At Risk Jobs</div>
                <div class="overflow-auto max-h-72">
                    <table class="w-full text-sm">
                        <thead><tr class="text-left border-b"><th class="py-2 pr-2">Job</th><th class="py-2 pr-2">Domain</th><th class="py-2 pr-2">Status</th><th class="py-2 pr-2">Risk</th></tr></thead>
                        <tbody>
                            @forelse(data_get($state,'at_risk_jobs',[]) as $job)
                                <tr class="border-b">
                                    <td class="py-2 pr-2"><a class="text-primary-600" href="{{ \App\Filament\Resources\ServiceJobResource::getUrl('view',['record'=>$job['id']]) }}">#{{ $job['id'] }}</a></td>
                                    <td class="py-2 pr-2">{{ $job['domain'] }}</td>
                                    <td class="py-2 pr-2">{{ $job['status_label'] }}</td>
                                    <td class="py-2 pr-2">{{ $job['risk_score'] }}</td>
                                </tr>
                            @empty
                                <tr><td class="py-3 text-gray-500" colspan="4">No at-risk jobs.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-filament::card>

            <x-filament::card>
                <div class="font-semibold mb-3">Open Exceptions</div>
                <div class="overflow-auto max-h-72">
                    <table class="w-full text-sm">
                        <thead><tr class="text-left border-b"><th class="py-2 pr-2">Type</th><th class="py-2 pr-2">Severity</th><th class="py-2 pr-2">Job</th><th class="py-2 pr-2">Owner</th></tr></thead>
                        <tbody>
                            @forelse(data_get($state,'open_exceptions',[]) as $ex)
                                <tr class="border-b">
                                    <td class="py-2 pr-2">{{ $ex['type_label'] }}</td>
                                    <td class="py-2 pr-2">{{ ucfirst($ex['severity']) }}</td>
                                    <td class="py-2 pr-2">#{{ $ex['job_id'] }}</td>
                                    <td class="py-2 pr-2">{{ $ex['owner_id'] ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr><td class="py-3 text-gray-500" colspan="4">No open exceptions.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-filament::card>

            <x-filament::card>
                <div class="font-semibold mb-3">Dispatch Pressure by Domain</div>
                <div class="space-y-2 text-sm">
                    @foreach(data_get($state,'dispatch_pressure_by_domain',[]) as $d)
                        <div class="flex justify-between"><span>{{ $d['domain'] }}</span><strong>{{ $d['count'] }}</strong></div>
                    @endforeach
                </div>
            </x-filament::card>

            <x-filament::card>
                <div class="font-semibold mb-3">Executors Availability</div>
                <div class="space-y-2 text-sm">
                    @foreach(data_get($state,'executors_availability',[]) as $e)
                        <div class="flex justify-between"><span>{{ ucfirst($e['status']) }}</span><strong>{{ $e['count'] }}</strong></div>
                    @endforeach
                </div>
            </x-filament::card>

            <x-filament::card class="xl:col-span-2">
                <div class="font-semibold mb-3">Recent Reassignments</div>
                <div class="overflow-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="text-left border-b"><th class="py-2 pr-2">Assignment</th><th class="py-2 pr-2">Job</th><th class="py-2 pr-2">Executor</th><th class="py-2 pr-2">At</th></tr></thead>
                        <tbody>
                            @forelse(data_get($state,'recent_reassignments',[]) as $r)
                                <tr class="border-b"><td class="py-2 pr-2">#{{ $r['assignment_id'] }}</td><td class="py-2 pr-2">#{{ $r['job_id'] }}</td><td class="py-2 pr-2">{{ $r['executor_id'] ?? '-' }}</td><td class="py-2 pr-2">{{ $r['at'] ? \Carbon\Carbon::parse($r['at'])->format('Y-m-d H:i') : '-' }}</td></tr>
                            @empty
                                <tr><td class="py-3 text-gray-500" colspan="4">No reassignments.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-filament::card>
        </div>
    </div>
</x-filament::page>

