<x-filament::page>
    <style>
        .bikube-dispatch-shell { background: linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%); padding: 0.25rem; border-radius: 1rem; }
        .bikube-dispatch-hero {
            background: radial-gradient(1200px 240px at 15% -10%, rgba(56, 189, 248, .20), transparent 60%),
                        radial-gradient(900px 220px at 90% -20%, rgba(99, 102, 241, .25), transparent 65%),
                        linear-gradient(120deg, #020617 0%, #0f172a 45%, #172554 100%);
        }
        .bikube-problem-order { box-shadow: 0 0 0 1px rgba(244,63,94,.25), 0 10px 24px rgba(225,29,72,.12); }
        .bikube-command-card { box-shadow: 0 8px 22px rgba(15,23,42,.08); }
        .bikube-kpi-urgent { box-shadow: inset 0 0 0 1px rgba(239,68,68,.2); }
        .bikube-kpi-pay { box-shadow: inset 0 0 0 1px rgba(168,85,247,.2); }
    </style>

    @php
        $filterLabels = [
            'all' => 'All',
            'waiting_dispatch' => 'Waiting dispatch',
            'unassigned' => 'Unassigned',
            'active' => 'Active',
            'urgent' => 'Urgent',
            'sla_risk' => 'SLA risk',
            'payment_problems' => 'Payment issues',
            'completed_today' => 'Completed today',
            'cancelled_disputed' => 'Cancelled / disputed',
        ];

        $statusClasses = [
            'completed' => 'bg-emerald-100 text-emerald-800',
            'cancelled' => 'bg-rose-100 text-rose-800',
            'disputed' => 'bg-rose-100 text-rose-800',
            'failed' => 'bg-rose-100 text-rose-800',
            'in_progress' => 'bg-blue-100 text-blue-800',
            'worker_en_route' => 'bg-blue-100 text-blue-800',
            'assigned' => 'bg-blue-100 text-blue-800',
            'worker_accepted' => 'bg-blue-100 text-blue-800',
            'waiting_dispatch' => 'bg-amber-100 text-amber-800',
            'pending' => 'bg-slate-100 text-slate-700',
        ];

        $paymentClasses = [
            'captured' => 'bg-emerald-100 text-emerald-800',
            'reserved' => 'bg-blue-100 text-blue-800',
            'authorized' => 'bg-blue-100 text-blue-800',
            'pending' => 'bg-amber-100 text-amber-800',
            'failed' => 'bg-rose-100 text-rose-800',
            'cancelled' => 'bg-rose-100 text-rose-800',
            'refunded' => 'bg-violet-100 text-violet-800',
            'partially_refunded' => 'bg-violet-100 text-violet-800',
        ];

        $kpiMeta = [
            'active_orders' => ['label' => 'Active orders', 'hint' => 'Currently in progress', 'accent' => 'from-blue-500 to-blue-700', 'icon' => 'A', 'border' => 'border-t-blue-500'],
            'waiting_dispatch' => ['label' => 'Waiting dispatch', 'hint' => 'Need routing now', 'accent' => 'from-amber-500 to-amber-600', 'icon' => 'W', 'border' => 'border-t-amber-500'],
            'unassigned' => ['label' => 'Unassigned', 'hint' => 'No worker assigned', 'accent' => 'from-orange-500 to-orange-600', 'icon' => 'U', 'border' => 'border-t-orange-500'],
            'urgent' => ['label' => 'Urgent', 'hint' => 'High-priority queue', 'accent' => 'from-rose-500 to-red-600', 'icon' => '!', 'border' => 'border-t-rose-500'],
            'payment_problems' => ['label' => 'Payment issues', 'hint' => 'Reserve/capture/refund needed', 'accent' => 'from-violet-500 to-rose-600', 'icon' => '$', 'border' => 'border-t-violet-500'],
            'completed_today' => ['label' => 'Completed today', 'hint' => 'Finished successfully', 'accent' => 'from-emerald-500 to-green-600', 'icon' => 'C', 'border' => 'border-t-emerald-500'],
            'cancelled_disputed' => ['label' => 'Cancelled / disputed', 'hint' => 'Needs review', 'accent' => 'from-rose-600 to-red-700', 'icon' => 'X', 'border' => 'border-t-rose-600'],
        ];
    @endphp

    <div class="bikube-dispatch-shell space-y-6">
        <section class="bikube-dispatch-hero relative overflow-hidden rounded-3xl p-7 text-white shadow-2xl ring-1 ring-white/10">
            <div class="pointer-events-none absolute -right-20 -top-20 h-56 w-56 rounded-full bg-indigo-500/20 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-20 -left-10 h-52 w-52 rounded-full bg-cyan-400/10 blur-3xl"></div>

            <div class="relative flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-4xl font-extrabold tracking-tight text-white">Dispatch Center</h1>
                    <p class="mt-2 text-sm text-slate-300">Real-time operations control for BiKuBe orders</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center rounded-full border border-indigo-300/70 bg-indigo-500/30 px-3 py-1 text-xs font-semibold text-indigo-50">
                        Wave 3B Admin OS v1
                    </span>
                    <button wire:click="reloadStats" class="rounded-lg bg-white/15 px-3 py-2 text-xs font-semibold text-white ring-1 ring-white/20 hover:bg-white/25">
                        Refresh
                    </button>
                    <a href="{{ url('/admin/orders') }}" class="rounded-lg bg-lime-400 px-3 py-2 text-xs font-bold text-slate-900 hover:bg-lime-300">
                        Open orders
                    </a>
                </div>
            </div>

            <div class="relative mt-5 grid gap-2 text-xs text-slate-200 md:grid-cols-3">
                <div class="rounded-xl bg-white/10 px-3 py-2 ring-1 ring-white/15">Live operations</div>
                <div class="rounded-xl bg-white/10 px-3 py-2 ring-1 ring-white/15">Payment controls</div>
                <div class="rounded-xl bg-white/10 px-3 py-2 ring-1 ring-white/15">SLA watch</div>
            </div>
        </section>

        <div class="rounded-2xl bg-white/95 p-3 shadow-sm ring-1 ring-slate-200 dark:bg-gray-900 dark:ring-gray-700">
            <div class="flex flex-wrap gap-2">
                @foreach($filterLabels as $key => $label)
                    @php $count = $key === 'all' ? collect($this->stats)->sum() : ($this->stats[$key] ?? 0); @endphp
                    <button
                        wire:click="$set('filter','{{ $key }}')"
                        class="inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-xs font-semibold transition {{ $this->filter === $key ? 'border-indigo-800 bg-indigo-800 text-white shadow-md' : 'border-slate-200 bg-white text-slate-700 hover:border-indigo-300 hover:bg-indigo-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800' }}"
                    >
                        <span>{{ $label }}</span>
                        <span class="rounded-full px-2 py-0.5 text-[10px] {{ $this->filter === $key ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">{{ $count }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3 md:grid-cols-4 xl:grid-cols-7">
            @foreach($kpiMeta as $key => $meta)
                @php $value = $this->stats[$key] ?? 0; @endphp
                <article class="relative overflow-hidden rounded-2xl border-t-4 {{ $meta['border'] }} bg-white p-4 shadow-md ring-1 ring-slate-200 {{ $key === 'urgent' ? 'bikube-kpi-urgent' : '' }} {{ $key === 'payment_problems' ? 'bikube-kpi-pay' : '' }}">
                    <div class="pointer-events-none absolute -right-6 -top-6 h-16 w-16 rounded-full bg-gradient-to-br {{ $meta['accent'] }} opacity-20"></div>
                    <div class="mb-3 flex items-center justify-between">
                        <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ $meta['label'] }}</span>
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-gradient-to-br {{ $meta['accent'] }} text-[10px] font-bold text-white">{{ $meta['icon'] }}</span>
                    </div>
                    <div class="text-3xl font-bold leading-none text-slate-900">{{ $value }}</div>
                    <p class="mt-2 text-xs text-slate-500">{{ $meta['hint'] }}</p>
                </article>
            @endforeach
        </div>

        @forelse($this->orders as $order)
            @php
                $statusClass = $statusClasses[$order->status] ?? 'bg-slate-100 text-slate-700';
                $paymentClass = $paymentClasses[$order->payment_status] ?? 'bg-slate-100 text-slate-700';
                $priorityClass = in_array(($order->priority ?? ''), ['urgent', 'high'], true) ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-700';
                $isProblem = in_array($order->status, ['cancelled', 'disputed', 'failed'], true)
                    || in_array($order->payment_status, ['failed', 'cancelled', 'refunded', 'partially_refunded'], true)
                    || (bool) ($order->sla_breach_risk ?? false)
                    || (($order->status === 'waiting_dispatch') && empty($order->assigned_to))
                    || in_array(($order->priority ?? ''), ['urgent', 'high'], true);
            @endphp

            <article class="bikube-command-card rounded-3xl border-l-4 p-5 ring-1 {{ $isProblem ? 'bikube-problem-order border-l-rose-500 bg-gradient-to-br from-rose-50 to-white ring-rose-200' : 'border-l-indigo-500 bg-white ring-slate-200' }}">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="text-2xl font-bold tracking-tight text-slate-900">{{ $order->order_number ?? ('#'.$order->id) }}</div>
                        <div class="mt-1 text-sm text-slate-500">ID {{ $order->id }} • {{ optional($order->created_at)->format('Y-m-d H:i') }}</div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if($isProblem)
                            <span class="rounded-full bg-rose-600 px-3 py-1 text-xs font-semibold text-white">Action needed</span>
                        @endif
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusClass }}">{{ $order->status ?? 'pending' }}</span>
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $paymentClass }}">{{ $order->payment_status ?? 'pending' }}</span>
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $priorityClass }}">{{ $order->priority ?? 'normal' }}</span>
                    </div>
                </div>

                <div class="mt-4 grid gap-3 md:grid-cols-4">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Client</div>
                        <div class="mt-1 text-base font-semibold text-slate-900">{{ $order->user->name ?? 'Guest' }}</div>
                        <div class="text-sm text-slate-600">{{ $order->user->email ?? '-' }}</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Worker</div>
                        <div class="mt-1 text-base font-semibold text-slate-900">{{ $order->assignedUser->name ?? 'Not assigned' }}</div>
                        <div class="text-sm text-slate-600">assigned_to: {{ $order->assigned_to ?? 'null' }}</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Route / SLA</div>
                        <div class="mt-1 text-sm text-slate-700">SLA risk: {{ ($order->sla_breach_risk ?? false) ? 'yes' : 'no' }}</div>
                        <div class="text-sm text-slate-700">ETA: {{ optional($order->eta_at)->format('Y-m-d H:i') ?? '-' }}</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Service / Scenario</div>
                        <div class="mt-1 text-sm text-slate-700">{{ data_get($order, 'service_type', '-') }}</div>
                        <div class="text-sm text-slate-700">{{ data_get($order, 'scenario_key', '-') }}</div>
                    </div>
                </div>

                <div class="mt-4 rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-100 to-white p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Main actions</div>
                        <div class="text-[11px] text-slate-500">Dispatch control panel</div>
                    </div>
                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-7">
                        <div class="xl:col-span-2">
                            <select wire:model="workerSelection.{{ $order->id }}" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm">
                                <option value="">Select worker...</option>
                                @foreach($this->workers as $worker)
                                    <option value="{{ $worker->id }}">{{ $worker->name }}</option>
                                @endforeach
                            </select>
                            @error("workerSelection.$order->id")
                                <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
                            @enderror
                        </div>
                        <button wire:click="assignSelectedWorker({{ $order->id }})" class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow hover:bg-indigo-700">Assign selected worker</button>
                        <button wire:click="unassignWorker({{ $order->id }})" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Unassign worker</button>
                        <button wire:click="changeStatus({{ $order->id }}, 'waiting_dispatch')" class="rounded-xl bg-blue-600 px-4 py-2 text-xs font-semibold text-white shadow hover:bg-blue-700">Dispatch</button>
                        <button wire:click="changeStatus({{ $order->id }}, 'assigned')" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Assigned</button>
                        <button wire:click="changeStatus({{ $order->id }}, 'completed')" class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow hover:bg-emerald-700">Complete</button>
                        <button wire:click="changeStatus({{ $order->id }}, 'cancelled')" class="rounded-xl bg-rose-600 px-4 py-2 text-xs font-semibold text-white shadow hover:bg-rose-700">Cancel</button>
                    </div>
                </div>

                <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Secondary controls</div>
                    <div class="grid gap-4 xl:grid-cols-4">
                        <section class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Override</div>
                            <input wire:model.defer="overrideReason.{{ $order->id }}" placeholder="Override reason" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                            @error("overrideReason.$order->id")
                                <div class="mt-1 text-xs text-rose-600">{{ $message }}</div>
                            @enderror
                            <button wire:click="applyOverride({{ $order->id }}, 'waiting_dispatch')" class="mt-3 w-full rounded-lg bg-amber-600 px-3 py-2 text-xs font-semibold text-white hover:bg-amber-700">Override status</button>
                        </section>

                        <section class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Payment</div>
                            <div class="grid gap-2">
                                <button wire:click="manualPayment({{ $order->id }}, 'reserve')" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Manual reserve</button>
                                <button wire:click="manualPayment({{ $order->id }}, 'capture')" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Manual capture</button>
                                <button wire:click="manualPayment({{ $order->id }}, 'refund')" class="rounded-lg bg-rose-600 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-700">Manual refund</button>
                            </div>
                        </section>

                        <section class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Notes</div>
                            <input wire:model.defer="internalNote.{{ $order->id }}" placeholder="Internal note" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                            <button wire:click="saveInternalNote({{ $order->id }})" class="mt-3 w-full rounded-lg bg-slate-800 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-900">Save internal note</button>
                        </section>

                        <section class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Links</div>
                            <div class="grid gap-2">
                                <a class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50" href="{{ url('/admin/orders/'.$order->id) }}">Open order</a>
                                @if($order->user_id)
                                    <a class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50" href="{{ url('/admin/users/'.$order->user_id.'/edit') }}">Open client</a>
                                @endif
                                @if($order->assigned_to)
                                    <a class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50" href="{{ url('/admin/users/'.$order->assigned_to.'/edit') }}">Open worker</a>
                                @endif
                                <a class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50" href="{{ url('/orders/'.$order->id.'/track') }}">Open tracker</a>
                            </div>
                        </section>
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm">
                <div class="mx-auto mb-3 inline-flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-600">0</div>
                <p class="text-base font-semibold text-slate-700">No orders in this filter</p>
                <p class="mt-1 text-sm text-slate-500">Try another filter or return to all orders.</p>
                <button wire:click="$set('filter','all')" class="mt-4 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700">Show all orders</button>
            </div>
        @endforelse
    </div>
</x-filament::page>
