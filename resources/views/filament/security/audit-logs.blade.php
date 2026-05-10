<x-filament::page>
    @php
        $rows = collect();

        if (\Illuminate\Support\Facades\Schema::hasTable('audit_logs')) {
            $rows = \App\Models\AuditLog::query()
                ->latest('created_at')
                ->limit(50)
                ->get();
        }
    @endphp

    <div class="space-y-4">
        <div>
            <h1 class="text-2xl font-semibold">Audit Logs</h1>
            <p class="text-sm text-gray-500">Read-only audit log viewer (last 50 records).</p>
        </div>

        @if($rows->isEmpty())
            <div class="text-sm text-gray-500">No records found.</div>
        @else
            <div class="overflow-x-auto rounded-lg border">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left">ID</th>
                            <th class="px-3 py-2 text-left">Action</th>
                            <th class="px-3 py-2 text-left">Model</th>
                            <th class="px-3 py-2 text-left">Actor</th>
                            <th class="px-3 py-2 text-left">IP</th>
                            <th class="px-3 py-2 text-left">Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                            <tr class="border-t">
                                <td class="px-3 py-2">{{ $row->id }}</td>
                                <td class="px-3 py-2">{{ $row->action }}</td>
                                <td class="px-3 py-2">{{ class_basename((string) $row->model_type) }}</td>
                                <td class="px-3 py-2">{{ $row->actor_user_id ?? '—' }}</td>
                                <td class="px-3 py-2">{{ $row->ip_address ?? '—' }}</td>
                                <td class="px-3 py-2">{{ optional($row->created_at)->format('Y-m-d H:i:s') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-filament::page>
