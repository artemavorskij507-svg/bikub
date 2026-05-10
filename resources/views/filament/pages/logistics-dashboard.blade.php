<x-filament-panels::page>
    <div class="grid gap-4 md:grid-cols-2">
        <div class="rounded-lg border p-4 bg-white">
            <h3 class="font-semibold">Logistics Dashboard</h3>
            <p class="text-sm text-slate-600">Active shipments: {{ $activeShipments ?? 'n/a' }}</p>
        </div>
        <div class="rounded-lg border p-4 bg-white">
            <h3 class="font-semibold">Realtime Map</h3>
            <div class="h-56 rounded bg-slate-100" id="logistics-map-canvas"></div>
        </div>
    </div>
</x-filament-panels::page>
