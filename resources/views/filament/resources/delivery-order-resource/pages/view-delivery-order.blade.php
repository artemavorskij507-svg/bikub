<x-filament::page>
    @php
        $delivery = $this->record;
        $order = $delivery->order;
        $metaRows = $this->deliveryMetadataRows();
        $orderable = $delivery->orderable;
    @endphp

    <div class="space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900">{{ $order->order_number ?? ('#'.$delivery->id) }}</h1>
                    <div class="mt-2 text-sm text-slate-500">Delivery ID {{ $delivery->id }} • Created {{ optional($delivery->created_at)->format('Y-m-d H:i:s') ?? '—' }}</div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-800">{{ (string) ($delivery->tracking_status ?? 'pending') }}</span>
                    <span class="rounded-full {{ $delivery->is_urgent ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-700' }} px-3 py-1 text-xs font-semibold">{{ $delivery->is_urgent ? 'urgent' : 'normal' }}</span>
                    <a href="{{ url('/admin/orders/'.$order?->id) }}" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Open order</a>
                </div>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Delivery details</h2>
                <div class="mt-2 space-y-1 text-sm text-slate-800">
                    <div><span class="text-slate-500">Type:</span> {{ (string) ($delivery->type?->value ?? $delivery->type ?? '—') }}</div>
                    <div><span class="text-slate-500">Tracking status:</span> {{ (string) ($delivery->tracking_status?->value ?? $delivery->tracking_status ?? '—') }}</div>
                    <div><span class="text-slate-500">Courier:</span> {{ $delivery->courier->name ?? $delivery->courier->email ?? '—' }}</div>
                    <div><span class="text-slate-500">Urgent:</span> {{ $delivery->is_urgent ? 'yes' : 'no' }}</div>
                    <div><span class="text-slate-500">Substitution policy:</span> {{ (string) ($delivery->substitution_policy?->value ?? $delivery->substitution_policy ?? '—') }}</div>
                </div>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Route</h2>
                <div class="mt-2 space-y-1 text-sm text-slate-800">
                    <div><span class="text-slate-500">Pickup:</span> {{ $delivery->pickup_address ?? '—' }}</div>
                    <div><span class="text-slate-500">Delivery:</span> {{ $delivery->delivery_address ?? '—' }}</div>
                    <div><span class="text-slate-500">ETA:</span> {{ optional($delivery->eta)->format('Y-m-d H:i') ?? '—' }}</div>
                    <div><span class="text-slate-500">Distance:</span> {{ $delivery->estimated_distance_km ?? '—' }}</div>
                    <div><span class="text-slate-500">Duration (min):</span> {{ $delivery->estimated_duration_minutes ?? '—' }}</div>
                </div>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Store / restaurant / bulky</h2>
                <div class="mt-2 space-y-1 text-sm text-slate-800">
                    <div><span class="text-slate-500">Orderable type:</span> {{ class_basename((string) $delivery->orderable_type) ?: '—' }}</div>
                    <div><span class="text-slate-500">Orderable ID:</span> {{ $delivery->orderable_id ?? '—' }}</div>
                    @if($orderable)
                        <div><span class="text-slate-500">Linked record:</span> yes</div>
                    @else
                        <div><span class="text-slate-500">Linked record:</span> no</div>
                    @endif
                </div>
            </article>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Metadata</h2>
            @if(empty($metaRows))
                <p class="mt-3 text-sm text-slate-500">No metadata.</p>
            @else
                <div class="mt-3 divide-y divide-slate-100">
                    @foreach($metaRows as $row)
                        <div class="grid grid-cols-12 gap-3 py-2">
                            <div class="col-span-4 text-xs font-semibold text-slate-500">{{ $row['key'] }}</div>
                            <div class="col-span-8 break-all text-sm text-slate-800">{{ $row['value'] }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</x-filament::page>
