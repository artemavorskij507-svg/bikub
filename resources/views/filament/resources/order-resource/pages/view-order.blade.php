<x-filament::page>
    <x-bikube.os-shell container-class="space-y-6">
        @php
            $order = $this->record;
            $metadataRows = $this->metadataRows();
            $eventRows = $this->eventRows();
            $related = $this->relatedDetailRows();
        @endphp

        <x-bikube.page-header
            eyebrow="Admin Order Cockpit"
            :title="'Order Summary: ' . ($order->order_number ?? ('#' . $order->id))"
            :subtitle="'ID ' . $order->id . ' • Created ' . (optional($order->created_at)->format('Y-m-d H:i:s') ?? '—')"
            :chips="[
                'Scenario: ' . ($order->scenario_key ?? '—'),
                'Service: ' . ($order->service_type ?? '—'),
            ]"
            badge="Wave 3B Admin OS v1"
            :open-url="url('/admin/orders/' . $order->id . '/edit')"
            open-label="Edit order"
            :refresh-url="url()->current()"
        >
            <x-slot:actions>
                <a href="{{ $this->trackerUrl() }}" class="bikube-os-btn bikube-os-btn-soft">Open tracker</a>
                <a href="{{ url('/admin/dispatch-center') }}" class="bikube-os-btn bikube-os-btn-soft">Open dispatch center</a>
                <x-bikube.status-badge :value="$order->status ?? 'pending'" type="status" />
                <x-bikube.status-badge :value="$order->payment_status ?? 'pending'" type="payment" />
                <x-bikube.status-badge :value="$order->priority ?? 'normal'" type="priority" />
            </x-slot:actions>
        </x-bikube.page-header>

        <section class="bikube-os-grid-3">
            <x-bikube.action-card title="Customer">
                <div class="bikube-os-info-grid">
                    <div class="bikube-os-info min-w-0"><p class="bikube-os-info-label">Name</p><p class="bikube-os-info-value break-words min-w-0 overflow-hidden">{{ $order->user->name ?? ($order->metadata['guest_name'] ?? '—') }}</p></div>
                    <div class="bikube-os-info min-w-0"><p class="bikube-os-info-label">Email</p><p class="bikube-os-info-value break-all min-w-0 overflow-hidden">{{ $order->user->email ?? ($order->metadata['guest_email'] ?? '—') }}</p></div>
                    <div class="bikube-os-info min-w-0"><p class="bikube-os-info-label">Phone</p><p class="bikube-os-info-value break-words min-w-0 overflow-hidden">{{ $order->user->phone ?? ($order->metadata['guest_phone'] ?? '—') }}</p></div>
                </div>
            </x-bikube.action-card>

            <x-bikube.action-card title="Route / Addresses">
                <div class="bikube-os-info-grid">
                    <div class="bikube-os-info min-w-0"><p class="bikube-os-info-label">Pickup</p><p class="bikube-os-info-value break-words min-w-0 overflow-hidden">{{ $order->metadata['pickup_address'] ?? '—' }}</p></div>
                    <div class="bikube-os-info min-w-0"><p class="bikube-os-info-label">Dropoff</p><p class="bikube-os-info-value break-words min-w-0 overflow-hidden">{{ $order->metadata['dropoff_address'] ?? '—' }}</p></div>
                    <div class="bikube-os-info"><p class="bikube-os-info-label">Time slot</p><p class="bikube-os-info-value">{{ $order->metadata['time_slot_start'] ?? '—' }}{{ isset($order->metadata['time_slot_end']) ? (' → ' . $order->metadata['time_slot_end']) : '' }}</p></div>
                    <div class="bikube-os-info"><p class="bikube-os-info-label">ETA</p><p class="bikube-os-info-value">{{ optional($order->eta_at)->format('Y-m-d H:i') ?? '—' }}</p></div>
                    <div class="bikube-os-info"><p class="bikube-os-info-label">SLA deadline</p><p class="bikube-os-info-value">{{ optional($order->sla_deadline)->format('Y-m-d H:i') ?? '—' }}</p></div>
                </div>
            </x-bikube.action-card>

            <x-bikube.action-card title="Assignment">
                <div class="bikube-os-info-grid">
                    <div class="bikube-os-info"><p class="bikube-os-info-label">Worker</p><p class="bikube-os-info-value">{{ $order->assignedUser->name ?? '—' }}</p></div>
                    <div class="bikube-os-info"><p class="bikube-os-info-label">Partner</p><p class="bikube-os-info-value">{{ $order->roadsidePartner->name ?? '—' }}</p></div>
                    <div class="bikube-os-info"><p class="bikube-os-info-label">Assignment status</p><p class="bikube-os-info-value">{{ $order->metadata['assignment_status'] ?? '—' }}</p></div>
                </div>
            </x-bikube.action-card>

            <x-bikube.action-card title="Payment">
                <div class="bikube-os-info-grid">
                    <div class="bikube-os-info"><p class="bikube-os-info-label">Status</p><p class="bikube-os-info-value">{{ $order->payment_status ?? '—' }}</p></div>
                    <div class="bikube-os-info"><p class="bikube-os-info-label">Estimated</p><p class="bikube-os-info-value">{{ $order->estimated_total ?? '—' }}</p></div>
                    <div class="bikube-os-info"><p class="bikube-os-info-label">Final</p><p class="bikube-os-info-value">{{ $order->final_price ?? '—' }}</p></div>
                    <div class="bikube-os-info"><p class="bikube-os-info-label">Currency</p><p class="bikube-os-info-value">{{ $order->currency ?? '—' }}</p></div>
                    <div class="bikube-os-info"><p class="bikube-os-info-label">Reference</p><p class="bikube-os-info-value">{{ $order->payment_intent_id ?? '—' }}</p></div>
                </div>
            </x-bikube.action-card>

            <x-bikube.action-card title="Scenario / Service">
                <div class="bikube-os-info-grid">
                    <div class="bikube-os-info"><p class="bikube-os-info-label">Scenario key</p><p class="bikube-os-info-value">{{ $order->scenario_key ?? '—' }}</p></div>
                    <div class="bikube-os-info"><p class="bikube-os-info-label">Service type</p><p class="bikube-os-info-value">{{ $order->service_type ?? '—' }}</p></div>
                    <div class="bikube-os-info min-w-0"><p class="bikube-os-info-label">Source</p><p class="bikube-os-info-value break-words min-w-0 overflow-hidden">{{ $order->metadata['source'] ?? '—' }}</p></div>
                    <div class="bikube-os-info min-w-0"><p class="bikube-os-info-label">Note</p><p class="bikube-os-info-value break-words min-w-0 overflow-hidden">{{ $order->notes ?? '—' }}</p></div>
                </div>
            </x-bikube.action-card>

            <x-bikube.action-card title="SLA / ETA">
                <div class="bikube-os-info-grid">
                    <div class="bikube-os-info"><p class="bikube-os-info-label">SLA risk</p><p class="bikube-os-info-value">{{ ($order->sla_breach_risk ?? false) ? 'yes' : 'no' }}</p></div>
                    <div class="bikube-os-info"><p class="bikube-os-info-label">Scheduled</p><p class="bikube-os-info-value">{{ optional($order->scheduled_at)->format('Y-m-d H:i') ?? '—' }}</p></div>
                    <div class="bikube-os-info"><p class="bikube-os-info-label">Started</p><p class="bikube-os-info-value">{{ optional($order->started_at)->format('Y-m-d H:i') ?? '—' }}</p></div>
                    <div class="bikube-os-info"><p class="bikube-os-info-label">Completed</p><p class="bikube-os-info-value">{{ optional($order->completed_at)->format('Y-m-d H:i') ?? '—' }}</p></div>
                </div>
            </x-bikube.action-card>
        </section>

        <x-bikube.action-card title="Metadata" subtitle="Compact summary with expandable details for long values.">
            @if (empty($metadataRows))
                <x-bikube.empty-state title="No metadata" message="No metadata attached to this order." />
            @else
                <div class="divide-y divide-slate-100">
                    @foreach ($metadataRows as $row)
                        <div class="grid grid-cols-12 gap-3 py-2">
                            <div class="col-span-4 text-xs font-semibold text-slate-500">{{ $row['key'] }}</div>
                            <div class="col-span-8 text-sm text-slate-800">
                                <div>{{ $row['value'] }}</div>
                                @if (!empty($row['expandable']) && !empty($row['expanded_value']))
                                    <details class="mt-1">
                                        <summary class="cursor-pointer text-xs font-medium text-slate-500 hover:text-slate-700">Show details</summary>
                                        <pre class="mt-2 overflow-x-auto rounded-lg bg-slate-900/95 p-3 text-xs text-slate-100">{{ $row['expanded_value'] }}</pre>
                                    </details>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-bikube.action-card>

        <x-bikube.action-card title="Events / History">
            @if (empty($eventRows))
                <x-bikube.empty-state title="No order events yet" message="Events will appear here when lifecycle or payment updates happen." />
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                                <th class="px-2 py-2">Time</th>
                                <th class="px-2 py-2">Event</th>
                                <th class="px-2 py-2">Transition</th>
                                <th class="px-2 py-2">Actor</th>
                                <th class="px-2 py-2">Payload</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($eventRows as $row)
                                <tr>
                                    <td class="px-2 py-2 text-slate-600">{{ $row['at'] }}</td>
                                    <td class="px-2 py-2 font-medium text-slate-800">{{ $row['type'] }}</td>
                                    <td class="px-2 py-2 text-slate-700">{{ $row['transition'] }}</td>
                                    <td class="px-2 py-2 text-slate-700">{{ $row['actor'] }}</td>
                                    <td class="px-2 py-2 text-slate-600">{{ $row['payload'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-bikube.action-card>

        <x-bikube.action-card title="Related domain details">
            <div class="bikube-os-grid-5">
                @foreach (['delivery' => 'Delivery', 'moving' => 'Moving', 'eco' => 'Eco', 'handyman' => 'Handyman', 'tow' => 'Tow / Roadside'] as $key => $label)
                    <article class="bikube-os-info">
                        <p class="bikube-os-info-label">{{ $label }}</p>
                        @if ($related[$key])
                            <p class="bikube-os-info-value">Linked record exists (ID: {{ $related[$key]->id ?? '—' }})</p>
                        @else
                            <p class="bikube-os-info-value">No related data.</p>
                        @endif
                    </article>
                @endforeach
            </div>
        </x-bikube.action-card>
    </x-bikube.os-shell>
</x-filament::page>
