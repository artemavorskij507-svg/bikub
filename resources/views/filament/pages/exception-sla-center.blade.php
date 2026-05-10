<x-filament::page>
    <div class="space-y-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <x-filament::card><div class="text-xs text-gray-500">Dispatch Warning</div><div class="text-2xl font-bold">{{ $slaSummary['dispatch_warning'] ?? 0 }}</div></x-filament::card>
            <x-filament::card><div class="text-xs text-gray-500">Dispatch Breached</div><div class="text-2xl font-bold text-danger-600">{{ $slaSummary['dispatch_breached'] ?? 0 }}</div></x-filament::card>
            <x-filament::card><div class="text-xs text-gray-500">Arrival Warning</div><div class="text-2xl font-bold">{{ $slaSummary['arrival_warning'] ?? 0 }}</div></x-filament::card>
            <x-filament::card><div class="text-xs text-gray-500">Arrival Breached</div><div class="text-2xl font-bold text-danger-600">{{ $slaSummary['arrival_breached'] ?? 0 }}</div></x-filament::card>
            <x-filament::card><div class="text-xs text-gray-500">Completion Warning</div><div class="text-2xl font-bold">{{ $slaSummary['completion_warning'] ?? 0 }}</div></x-filament::card>
            <x-filament::card><div class="text-xs text-gray-500">Completion Breached</div><div class="text-2xl font-bold text-danger-600">{{ $slaSummary['completion_breached'] ?? 0 }}</div></x-filament::card>
            <x-filament::card><div class="text-xs text-gray-500">Open Exceptions</div><div class="text-2xl font-bold">{{ count($openExceptions ?? []) }}</div></x-filament::card>
            <x-filament::card><div class="text-xs text-gray-500">Total SLA Timers</div><div class="text-2xl font-bold">{{ $slaSummary['total_timers'] ?? 0 }}</div></x-filament::card>
        </div>

        <x-filament::card>
            <div class="overflow-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left border-b">
                            <th class="py-2 pr-2">Exception</th>
                            <th class="py-2 pr-2">Job</th>
                            <th class="py-2 pr-2">Severity</th>
                            <th class="py-2 pr-2">Status</th>
                            <th class="py-2 pr-2">Detected</th>
                            <th class="py-2 pr-2">Summary</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($openExceptions ?? [] as $exception)
                            <tr class="border-b">
                                <td class="py-2 pr-2">{{ $exception['type_label'] ?? $exception['type'] ?? '-' }}</td>
                                <td class="py-2 pr-2">#{{ $exception['job_id'] ?? '-' }}</td>
                                <td class="py-2 pr-2">{{ $exception['severity'] ?? '-' }}</td>
                                <td class="py-2 pr-2">{{ $exception['status'] ?? '-' }}</td>
                                <td class="py-2 pr-2">{{ !empty($exception['detected_at']) ? \Carbon\Carbon::parse($exception['detected_at'])->format('Y-m-d H:i') : '-' }}</td>
                                <td class="py-2 pr-2">{{ $exception['sla_metric'] ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::card>
    </div>
</x-filament::page>
