<x-filament::page>
    @if(empty($contracts))
        <div class="rounded border bg-white p-4 text-sm text-gray-600">No contracts table data yet (migration pending or empty).</div>
    @else
        <div class="overflow-x-auto rounded-lg border bg-white">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr><th class="px-3 py-2 text-left">ID</th><th class="px-3 py-2 text-left">Status</th><th class="px-3 py-2 text-left">Sent</th><th class="px-3 py-2 text-left">Signed</th><th class="px-3 py-2 text-left">Action</th></tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($contracts as $c)
                        <tr>
                            <td class="px-3 py-2">{{ $c['id'] }}</td>
                            <td class="px-3 py-2">{{ $c['status'] }}</td>
                            <td class="px-3 py-2">{{ $c['sent_at'] ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $c['signed_at'] ?? '-' }}</td>
                            <td class="px-3 py-2">
                                <button wire:click="markSigned({{ $c['id'] }})" class="text-blue-700">mark signed</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-filament::page>

