<div class="fi-wi-widget">
    <x-filament::card>
        <div class="font-semibold mb-3">Recent Reassignments</div>
        <div class="overflow-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left border-b">
                        <th class="py-2 pr-2">Assignment</th>
                        <th class="py-2 pr-2">Job</th>
                        <th class="py-2 pr-2">Executor</th>
                        <th class="py-2 pr-2">At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr class="border-b">
                            <td class="py-2 pr-2">#{{ $item['assignment_id'] }}</td>
                            <td class="py-2 pr-2">#{{ $item['job_id'] }}</td>
                            <td class="py-2 pr-2">{{ $item['executor_id'] ?? '-' }}</td>
                            <td class="py-2 pr-2">{{ !empty($item['at']) ? \Carbon\Carbon::parse($item['at'])->format('Y-m-d H:i') : '-' }}</td>
                        </tr>
                    @empty
                        <tr><td class="py-3 text-gray-500" colspan="4">No reassignments.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::card>
</div>

