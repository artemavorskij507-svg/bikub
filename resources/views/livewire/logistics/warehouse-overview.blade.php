<div class="card p-4">
    <h3 class="text-lg font-semibold mb-3">Warehouse Overview</h3>
    <div class="grid md:grid-cols-2 gap-3">
        @foreach($warehouses as $warehouse)
            <div class="border rounded p-3">
                <div class="font-semibold">{{ $warehouse->name }}</div>
                <div class="text-sm text-slate-600">Zones: {{ $warehouse->zones->count() }}</div>
            </div>
        @endforeach
    </div>
</div>
