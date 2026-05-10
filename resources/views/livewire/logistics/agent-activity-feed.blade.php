<div class="card p-4">
    <h3 class="text-lg font-semibold mb-3">Agent Activity Feed</h3>
    <ul class="space-y-2 text-sm">
        @forelse($activities as $activity)
            <li>{{ $activity->created_at }} - {{ $activity->event_type ?? 'event' }}</li>
        @empty
            <li class="text-slate-500">No agent activity yet.</li>
        @endforelse
    </ul>
</div>
