<div class="fi-wi-widget">
    <x-filament::card>
        <div class="font-semibold mb-3">Executors Availability</div>
        <div class="space-y-2 text-sm">
            @forelse($items as $item)
                <div class="flex justify-between">
                    <span>{{ ucfirst($item['status']) }}</span>
                    <strong>{{ $item['count'] }}</strong>
                </div>
            @empty
                <div class="text-gray-500">No data</div>
            @endforelse
        </div>
    </x-filament::card>
</div>

