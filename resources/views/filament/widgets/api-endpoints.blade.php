<div class="p-4 space-y-4">
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold">🌐 API Endpoints</h2>
        <div class="text-sm text-gray-600">Total: {{ $totalCount }} / Notifications: {{ $notificationsCount }}</div>
    </div>

    <div class="overflow-x-auto border rounded">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2 text-left">Method</th>
                    <th class="px-3 py-2 text-left">URI</th>
                    <th class="px-3 py-2 text-left">Name</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($endpoints as $e)
                    <tr class="border-t">
                        <td class="px-3 py-2 font-mono">{{ $e['method'] }}</td>
                        <td class="px-3 py-2 font-mono">/{{ $e['uri'] }}</td>
                        <td class="px-3 py-2">{{ $e['name'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div>
        <h3 class="text-md font-semibold mb-2">Notifications</h3>
        <div class="overflow-x-auto border rounded">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left">Method</th>
                        <th class="px-3 py-2 text-left">URI</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($notifications as $n)
                        <tr class="border-t">
                            <td class="px-3 py-2 font-mono">{{ $n['method'] }}</td>
                            <td class="px-3 py-2 font-mono">/{{ $n['uri'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

