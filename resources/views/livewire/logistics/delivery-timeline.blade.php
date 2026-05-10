<div class="card p-4">
    <h3 class="text-lg font-semibold mb-3">Delivery Timeline</h3>
    <ul class="space-y-2 text-sm">
        @forelse(($shipment?->trackingEvents ?? collect()) as $event)
            <li>{{ $event->happened_at }} - {{ $event->status }} - {{ $event->message }}</li>
        @empty
            <li class="text-slate-500">No tracking events yet.</li>
        @endforelse
    </ul>
</div>
