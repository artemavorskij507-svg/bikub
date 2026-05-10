<x-filament::page>
    <div>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />

    <div class="space-y-4">
        <x-filament::card>
            <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-8 gap-3 text-sm">
                <div>Active jobs: <strong>{{ data_get($summary,'active_jobs',0) }}</strong></div>
                <div>Pending: <strong>{{ data_get($summary,'pending_dispatch',0) }}</strong></div>
                <div>Assigned: <strong>{{ data_get($summary,'assigned',0) }}</strong></div>
                <div>In progress: <strong>{{ data_get($summary,'in_progress',0) }}</strong></div>
                <div>At risk: <strong>{{ data_get($summary,'at_risk',0) }}</strong></div>
                <div>Open exceptions: <strong>{{ data_get($summary,'open_exceptions',0) }}</strong></div>
                <div>Avg dispatch: <strong>{{ data_get($summary,'avg_dispatch_time',0) }}m</strong></div>
                <div>Avg delay: <strong>{{ data_get($summary,'avg_arrival_delay',0) }}m</strong></div>
            </div>
        </x-filament::card>

        <x-filament::card>
            @include('filament.components.ops.partials.sticky-incident-banner')
            @include('filament.components.ops.partials.workbench-latency-strip')
            @include('filament.components.ops.partials.workbench-triage-strip')
            @include('filament.components.ops.partials.saved-filters')
            @include('filament.components.ops.partials.workbench-bulk-triage-panel')
        </x-filament::card>

        <x-filament::card>
            <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
                <select wire:model.defer="filters.domain" class="block w-full rounded-lg border-gray-300">
                    <option value="">All domains</option><option value="delivery">Delivery</option><option value="handyman">Handyman</option><option value="moving">Moving</option><option value="roadside">Roadside</option><option value="social_care">Social Care</option>
                </select>
                <input wire:model.defer="filters.zone" placeholder="Zone" class="block w-full rounded-lg border-gray-300" />
                <select wire:model.defer="filters.status" class="block w-full rounded-lg border-gray-300">
                    <option value="">All statuses</option><option value="pending_dispatch">Pending</option><option value="assigned">Assigned</option><option value="en_route">En route</option><option value="arrived">Arrived</option><option value="in_progress">In progress</option>
                </select>
                <label class="inline-flex items-center gap-2"><input type="checkbox" wire:model.defer="filters.at_risk_only"> <span class="text-sm">At risk only</span></label>
                <label class="inline-flex items-center gap-2"><input type="checkbox" wire:model.defer="filters.exceptions_only"> <span class="text-sm">Exceptions only</span></label>
                <x-filament::button wire:click="refreshData" color="primary">Apply filters</x-filament::button>
            </div>
        </x-filament::card>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
            <x-filament::card class="xl:col-span-2">
                <div id="ops-map" style="height: 560px; border-radius: 10px;"></div>
            </x-filament::card>

            <x-filament::card>
                <div class="font-semibold mb-3">Details</div>
                <div id="ops-drawer" class="text-sm text-gray-700 space-y-2">
                    <p>Выберите маркер на карте, чтобы открыть рабочий drawer.</p>
                </div>
                <div class="mt-3">
                    @include('filament.components.ops.partials.candidate-compare')
                    @include('filament.components.ops.partials.eta-strategy-diff')
                    @include('filament.components.ops.partials.replan-recommendations')
                    @include('filament.components.ops.partials.routing-shadow-metrics')
                </div>
            </x-filament::card>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script>
        (function () {
            const jobs = @json(data_get($state, 'jobs', []));
            const executors = @json(data_get($state, 'executors', []));
            const exceptions = @json(data_get($state, 'exceptions', []));
            const organizationId = @json((string) (auth()->user()->organization_id ?? auth()->user()->default_org_id ?? ''));
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

            const drawer = document.getElementById('ops-drawer');
            const stickyRoot = document.getElementById('sticky-incident-banner');
            const latencyRoot = document.getElementById('workbench-latency-strip');
            const triageRoot = document.getElementById('workbench-triage-strip');
            const savedFiltersRoot = document.getElementById('workbench-saved-filters');
            const bulkPanelRoot = document.getElementById('workbench-bulk-triage-panel');
            const compareRoot = document.getElementById('candidate-compare-panel');
            const etaDiffRoot = document.getElementById('eta-strategy-diff-panel');
            const replanRoot = document.getElementById('replan-recommendations-panel');
            const routingShadowMetricsRoot = document.getElementById('routing-shadow-metrics-panel');
            const mapRoot = document.getElementById('ops-map');
            if (!mapRoot || typeof L === 'undefined' || !drawer) {
                return;
            }

            const state = {
                drawerMode: null,
                selectedJobId: null,
                selectedExecutorId: null,
                selectedExceptionId: null,
                drawerData: null,
                compareLeftExecutorId: null,
                compareRightExecutorId: null,
            };
            let map = null;
            const inFlightIdempotency = new Map();

            const endpoints = {
                jobDrawer: (id) => `/api/ops/jobs/${id}/drawer`,
                executorDrawer: (id) => `/api/ops/executors/${id}/drawer`,
                exceptionDrawer: (id) => `/api/ops/exceptions/${id}/drawer`,
                manualDispatch: (id) => `/api/ops/jobs/${id}/manual-dispatch`,
                manualReassign: (id) => `/api/ops/jobs/${id}/manual-reassign`,
                exceptionAcknowledge: (id) => `/api/ops/exceptions/${id}/acknowledge`,
                exceptionResolve: (id) => `/api/ops/exceptions/${id}/resolve-workbench`,
                candidateCompare: (id, left, right) => `/api/ops/jobs/${id}/candidate-compare?left_executor_id=${left ?? ''}&right_executor_id=${right ?? ''}`,
                stickyIncidents: () => '/api/ops/workbench/sticky-incidents',
                latency: () => '/api/ops/workbench/latency',
                triage: () => '/api/ops/workbench/triage',
                savedFilters: () => '/api/ops/workbench/saved-filters',
                bulkAction: () => '/api/ops/workbench/bulk-action',
                replanRecommendations: () => '/api/ops/workbench/replan-recommendations',
                routingShadowMetrics: () => '/api/ops/workbench/routing-shadow-metrics?days=3',
                routingProviderHealth: () => '/api/ops/workbench/routing-provider-health',
            };

            const apiGet = async (url) => {
                const response = await fetch(url, { credentials: 'include' });
                if (!response.ok) throw new Error(`Request failed: ${response.status}`);
                return response.json();
            };

            const parseApiError = async (response) => {
                try {
                    const json = await response.json();
                    return json?.message || `Request failed: ${response.status}`;
                } catch (_) {
                    return `Request failed: ${response.status}`;
                }
            };

            const getIdempotencyKey = (idemScope) => {
                const existing = inFlightIdempotency.get(idemScope);
                if (existing) return existing;
                const created = (window.crypto?.randomUUID?.() ?? `${Date.now()}-${Math.random()}`).toString();
                inFlightIdempotency.set(idemScope, created);
                return created;
            };

            const apiPost = async (url, payload = {}, idemScope = 'default') => {
                const idemKey = getIdempotencyKey(idemScope);
                const response = await fetch(url, {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Idempotency-Key': idemKey,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });
                if (!response.ok) {
                    throw new Error(await parseApiError(response));
                }
                inFlightIdempotency.delete(idemScope);
                return response.json();
            };

            const setNotice = (message) => {
                drawer.innerHTML = `<p>${message}</p>`;
            };

            const collectCurrentFilters = () => ({
                domain: document.querySelector('[wire\\:model\\.defer="filters.domain"]')?.value ?? '',
                zone: document.querySelector('[wire\\:model\\.defer="filters.zone"]')?.value ?? '',
                status: document.querySelector('[wire\\:model\\.defer="filters.status"]')?.value ?? '',
                at_risk_only: !!document.querySelector('[wire\\:model\\.defer="filters.at_risk_only"]')?.checked,
                exceptions_only: !!document.querySelector('[wire\\:model\\.defer="filters.exceptions_only"]')?.checked,
            });

            const applyFiltersToUi = (filters = {}) => {
                const map = {
                    domain: '[wire\\:model\\.defer="filters.domain"]',
                    zone: '[wire\\:model\\.defer="filters.zone"]',
                    status: '[wire\\:model\\.defer="filters.status"]',
                };
                Object.entries(map).forEach(([key, selector]) => {
                    const input = document.querySelector(selector);
                    if (!input || filters[key] === undefined) return;
                    input.value = String(filters[key] ?? '');
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                });

                const boolMap = {
                    at_risk_only: '[wire\\:model\\.defer="filters.at_risk_only"]',
                    exceptions_only: '[wire\\:model\\.defer="filters.exceptions_only"]',
                };
                Object.entries(boolMap).forEach(([key, selector]) => {
                    const input = document.querySelector(selector);
                    if (!input || filters[key] === undefined) return;
                    input.checked = !!filters[key];
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                });

                document.querySelector('[wire\\:click="refreshData"]')?.click();
            };

            const reasonLabel = (reason) => {
                const normalized = String(reason ?? '').trim();
                if (!normalized) return 'Unknown reason';

                if (normalized.startsWith('missing_equipment:') || normalized.startsWith('missing_required_equipment:')) {
                    const item = normalized.split(':').slice(1).join(':').trim();
                    return item ? `Missing equipment: ${item}` : 'Missing required equipment';
                }

                if (normalized.startsWith('missing_skill:') || normalized.startsWith('missing_required_skills:')) {
                    const item = normalized.split(':').slice(1).join(':').trim();
                    return item ? `Missing skill: ${item}` : 'Missing required skill';
                }

                const labels = {
                    out_of_shift: 'After shift end',
                    after_shift_end: 'After shift end',
                    before_shift_start: 'Before shift start',
                    executor_on_break: 'Executor on break',
                    time_window_miss: 'Misses promised window',
                    capacity_mismatch: 'Capacity mismatch',
                    missing_required_equipment: 'Missing required equipment',
                    missing_required_skills: 'Missing required skill',
                    missing_roadside_capability: 'Missing roadside capability',
                    no_executor_found: 'No executor found',
                };

                return labels[normalized] || normalized.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
            };

            const renderStickyIncidentBanner = (payload) => {
                if (!stickyRoot) return;

                const items = Array.isArray(payload?.items) ? payload.items : [];
                const stickyItems = items.filter((item) => Number(item.count ?? 0) > 0);

                if (!stickyItems.length) {
                    stickyRoot.innerHTML = '<div class="rounded border border-green-300 bg-green-50 px-3 py-2 text-xs text-green-700">No sticky incidents right now.</div>';
                    return;
                }

                const color = (severity) => {
                    if (severity === 'danger') return 'border-red-300 bg-red-50 text-red-700';
                    if (severity === 'warning') return 'border-amber-300 bg-amber-50 text-amber-700';
                    return 'border-blue-300 bg-blue-50 text-blue-700';
                };

                stickyRoot.innerHTML = `
                    <div class="rounded border border-red-200 bg-red-50/60 px-3 py-2">
                        <div class="text-xs font-semibold uppercase tracking-wide text-red-800 mb-2">Sticky incident banner</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-2">
                            ${stickyItems.map((item) => `
                                <div class="rounded border px-2 py-2 ${color(item.severity)}">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="text-[11px] font-semibold">${item.label ?? '-'}</div>
                                        <div class="text-sm font-bold">${Number(item.count ?? 0)}</div>
                                    </div>
                                    <div class="text-[11px] mt-1">${item.description ?? ''}</div>
                                    <div class="flex flex-wrap gap-1 mt-2">
                                        <button data-action="sticky-filter" data-filter='${JSON.stringify(item.filter ?? {}).replace(/'/g, '&apos;')}' class="px-2 py-1 text-[11px] rounded border">Apply filter</button>
                                        ${item.focus_job_id ? `<button data-action="sticky-open-job" data-job-id="${item.focus_job_id}" class="px-2 py-1 text-[11px] rounded border">Open on map</button>` : ''}
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
            };

            const loadStickyIncidentBanner = async () => {
                try {
                    const payload = await apiGet(endpoints.stickyIncidents());
                    renderStickyIncidentBanner(payload);
                } catch (_) {
                    if (stickyRoot) {
                        stickyRoot.innerHTML = '<div class="text-xs text-gray-500">Sticky incidents unavailable.</div>';
                    }
                }
            };

            const renderLatencyStrip = (payload) => {
                if (!latencyRoot) return;

                const cards = Array.isArray(payload?.cards) ? payload.cards : [];
                if (!cards.length) {
                    latencyRoot.innerHTML = '<div class="text-xs text-gray-500">Latency metrics unavailable.</div>';
                    return;
                }

                const color = (severity) => {
                    if (severity === 'danger') return 'border-red-300 bg-red-50 text-red-700';
                    if (severity === 'warning') return 'border-amber-300 bg-amber-50 text-amber-700';
                    return 'border-slate-300 bg-slate-50 text-slate-700';
                };

                latencyRoot.innerHTML = `
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Action latency visibility</div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                        ${cards.map((card) => `
                            <div class="rounded border px-2 py-2 ${color(card.severity)}">
                                <div class="text-[11px]">${card.label ?? '-'}</div>
                                <div class="text-sm font-semibold">${Number(card.count ?? 0)}</div>
                                <div class="text-[11px]">max age: ${card.max_age_human ?? 'n/a'}</div>
                                <div class="flex flex-wrap gap-1 mt-1">
                                    <button data-action="latency-filter" data-filter='${JSON.stringify(card.filter ?? {}).replace(/'/g, '&apos;')}' class="px-2 py-1 text-[11px] rounded border">Filter</button>
                                    ${card.focus_job_id ? `<button data-action="latency-open-job" data-job-id="${card.focus_job_id}" class="px-2 py-1 text-[11px] rounded border">Open</button>` : ''}
                                </div>
                            </div>
                        `).join('')}
                    </div>
                `;
            };

            const loadLatencyStrip = async () => {
                try {
                    const payload = await apiGet(endpoints.latency());
                    renderLatencyStrip(payload);
                } catch (_) {
                    if (latencyRoot) {
                        latencyRoot.innerHTML = '<div class="text-xs text-gray-500">Latency unavailable.</div>';
                    }
                }
            };

            const renderBulkTriagePanel = () => {
                if (!bulkPanelRoot) return;

                bulkPanelRoot.innerHTML = `
                    <div class="rounded border border-gray-300 bg-white px-3 py-2">
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Bulk triage panel</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            <input id="bulk-exception-ids" class="rounded border-gray-300 text-xs" placeholder="Exception IDs (comma separated)" />
                            <input id="bulk-job-ids" class="rounded border-gray-300 text-xs" placeholder="Job IDs (comma separated)" />
                        </div>
                        <div class="flex flex-wrap gap-1 mt-2">
                            <button data-action="bulk-panel-use-current" class="px-2 py-1 text-xs rounded border">Use current drawer</button>
                            <button data-action="bulk-panel-action" data-bulk-action="exceptions_bulk_acknowledge" class="px-2 py-1 text-xs rounded border">Bulk acknowledge</button>
                            <button data-action="bulk-panel-action" data-bulk-action="exceptions_bulk_resolve" class="px-2 py-1 text-xs rounded border">Bulk resolve</button>
                            <button data-action="bulk-panel-action" data-bulk-action="jobs_bulk_reassign_request" class="px-2 py-1 text-xs rounded border">Bulk reassign request</button>
                            <button data-action="bulk-panel-action" data-bulk-action="jobs_bulk_assign_dispatcher_queue" class="px-2 py-1 text-xs rounded border">Bulk assign to dispatcher queue</button>
                            <button data-action="bulk-panel-open-map" class="px-2 py-1 text-xs rounded border">Bulk open on map</button>
                        </div>
                        <div id="bulk-triage-feedback" class="text-[11px] text-gray-500 mt-2"></div>
                    </div>
                `;
            };

            const parseIdList = (value) => (value || '')
                .split(',')
                .map((part) => Number(String(part).trim()))
                .filter((id) => Number.isFinite(id) && id > 0);

            const refreshWorkbenchSignals = async () => {
                await Promise.all([
                    loadStickyIncidentBanner(),
                    loadLatencyStrip(),
                    loadTriageStrip(),
                    loadReplanRecommendations(state.selectedJobId || null),
                    loadRoutingShadowMetrics(),
                ]);
            };

            const renderTriageStrip = (data) => {
                if (!triageRoot) return;
                const cards = Array.isArray(data?.cards) ? data.cards : [];
                if (!cards.length) {
                    triageRoot.innerHTML = '<div class="text-xs text-gray-500">No triage signals right now.</div>';
                    return;
                }

                triageRoot.innerHTML = `
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Priority triage</div>
                    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-2">
                        ${cards.map((card) => {
                            const count = Number(card.count ?? 0);
                            const color = card.severity === 'danger' ? 'border-red-300 bg-red-50 text-red-700'
                                : card.severity === 'warning' ? 'border-amber-300 bg-amber-50 text-amber-700'
                                : 'border-blue-300 bg-blue-50 text-blue-700';
                            return `
                                <button data-action="triage-filter" data-filter='${JSON.stringify(card.filter ?? {}).replace(/'/g, '&apos;')}' class="text-left rounded border px-2 py-2 ${color}">
                                    <div class="text-[11px]">${card.label ?? '-'}</div>
                                    <div class="text-sm font-semibold">${count}</div>
                                </button>
                            `;
                        }).join('')}
                    </div>
                `;
            };

            const loadTriageStrip = async () => {
                try {
                    const triage = await apiGet(endpoints.triage());
                    renderTriageStrip(triage);
                } catch (_) {
                    if (triageRoot) {
                        triageRoot.innerHTML = '<div class="text-xs text-gray-500">Triage unavailable.</div>';
                    }
                }
            };

            const renderSavedFilters = (items) => {
                if (!savedFiltersRoot) return;
                if (!Array.isArray(items) || !items.length) {
                    savedFiltersRoot.innerHTML = `
                        <div class="flex items-center justify-between gap-2">
                            <div class="text-xs text-gray-500">No saved filters yet.</div>
                            <button data-action="save-current-filter" class="px-2 py-1 text-xs rounded border">Save current filter</button>
                        </div>
                    `;
                    return;
                }

                savedFiltersRoot.innerHTML = `
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Saved filters</div>
                        <button data-action="save-current-filter" class="px-2 py-1 text-xs rounded border">Save current filter</button>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        ${items.map((item) => `
                            <div class="inline-flex items-center gap-1 rounded border px-2 py-1 text-xs">
                                <button data-action="apply-saved-filter" data-filters='${JSON.stringify(item.filters ?? {}).replace(/'/g, '&apos;')}' class="text-left">
                                    ${item.name}
                                </button>
                                <button data-action="delete-saved-filter" data-filter-id="${item.id}" class="text-red-600">x</button>
                            </div>
                        `).join('')}
                    </div>
                `;
            };

            const loadSavedFilters = async () => {
                try {
                    const payload = await apiGet(endpoints.savedFilters());
                    renderSavedFilters(payload.filters || []);
                } catch (_) {
                    if (savedFiltersRoot) {
                        savedFiltersRoot.innerHTML = '<div class="text-xs text-gray-500">Saved filters unavailable.</div>';
                    }
                }
            };

            const renderCandidateCompare = (payload) => {
                if (!compareRoot) return;

                const job = payload?.job || {};
                const left = payload?.left;
                const right = payload?.right;
                const selectedExecutorId = Number(payload?.selected_executor_id ?? 0) || null;
                const recommendedExecutorId = Number(payload?.recommended_executor_id ?? 0) || null;

                const renderChecks = (candidate) => {
                    const shift = candidate?.checks?.shift_fit || {};
                    const windowFit = candidate?.checks?.time_window_fit || {};
                    const capacity = candidate?.checks?.capacity_fit || {};

                    const shiftLine = shift.eligible === true
                        ? 'Shift: pass'
                        : (shift.eligible === false ? `Shift: fail (${reasonLabel(shift.reason ?? 'out_of_shift')})` : 'Shift: n/a');
                    const windowLine = windowFit.fits === true
                        ? `Window: pass (${windowFit.risk ?? 'low'} risk)`
                        : (windowFit.fits === false ? 'Window: fail (Misses promised window)' : 'Window: n/a');
                    const capacityLine = capacity.fits === true
                        ? 'Capacity: pass'
                        : (capacity.fits === false ? `Capacity: fail (${reasonLabel(capacity.reason ?? 'capacity_mismatch')})` : 'Capacity: n/a');

                    return `<div class="text-[11px] text-gray-600"><div>${shiftLine}</div><div>${windowLine}</div><div>${capacityLine}</div></div>`;
                };

                const renderModifiers = (candidate) => {
                    const modifiers = candidate?.modifiers ? Object.values(candidate.modifiers) : [];
                    if (!modifiers.length) {
                        return '<div class="text-[11px] text-gray-500">No modifiers.</div>';
                    }

                    return `<div class="text-[11px] text-gray-600">${modifiers.map((modifier) => `<div>${modifier.label ?? '-'}: ${modifier.formatted ?? modifier.value ?? 0}</div>`).join('')}</div>`;
                };

                const renderRuntime = (candidate) => {
                    const runtime = candidate?.runtime?.effective_rule_values || {};
                    const weights = runtime.weights || {};
                    const modifiers = runtime.modifiers || {};
                    return `
                        <div class="text-[11px] text-gray-600">
                            <div>ETA weight: ${weights.eta ?? 'n/a'}</div>
                            <div>Window weight: ${weights.time_window_fit ?? 'n/a'}</div>
                            <div>Capacity weight: ${weights.capacity_fit ?? 'n/a'}</div>
                            <div>Emergency boost: ${modifiers.emergency_boost ?? 'n/a'}</div>
                        </div>
                    `;
                };

                const renderEtaDiff = (candidate) => {
                    const routing = candidate?.routing || {};
                    if (!routing.routing_available) {
                        return `<div class="text-[11px] text-gray-500">Routing ETA unavailable${routing.routing_error ? ` (${routing.routing_error})` : ''}</div>`;
                    }

                    const delta = Number(routing.eta_delta_seconds ?? 0);
                    const sign = delta > 0 ? '+' : '';

                    return `
                        <div class="text-[11px] text-gray-600">
                            <div>Heuristic: ${routing.heuristic_eta_seconds ?? 'n/a'}s | Routing: ${routing.routing_eta_seconds ?? 'n/a'}s</div>
                            <div>Delta: ${sign}${delta}s (${routing.delta_percent ?? 'n/a'}%) | ${routing.significance ?? 'unavailable'}</div>
                            <div>Would change ranking: ${routing.would_change_ranking ? 'yes' : 'no'}</div>
                        </div>
                    `;
                };

                const card = (candidate, label) => {
                    if (!candidate) {
                        return `<div class="rounded border px-2 py-2 text-xs text-gray-500">${label}: not selected</div>`;
                    }

                    const executorId = Number(candidate.executor_id || 0);
                    const eligible = !!candidate.is_eligible;
                    const disabledClass = eligible ? '' : 'opacity-50 cursor-not-allowed';
                    const selectedBadge = selectedExecutorId && executorId === selectedExecutorId
                        ? '<span class="px-1 py-0.5 rounded bg-green-100 text-green-700 text-[10px]">Selected</span>'
                        : '';
                    const recommendedBadge = recommendedExecutorId && executorId === recommendedExecutorId
                        ? '<span class="px-1 py-0.5 rounded bg-blue-100 text-blue-700 text-[10px]">Recommended</span>'
                        : '';
                    const eligibleBadge = eligible
                        ? '<span class="px-1 py-0.5 rounded bg-emerald-100 text-emerald-700 text-[10px]">Eligible</span>'
                        : '<span class="px-1 py-0.5 rounded bg-red-100 text-red-700 text-[10px]">Ineligible</span>';
                    const reason = candidate.rejection_reason_label || candidate.rejection_reason || '';

                    return `
                        <div class="rounded border px-2 py-2 text-xs space-y-2">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <div class="font-medium">${label}: ${candidate.executor_name ?? candidate.display_name ?? '-'}</div>
                                    <div>Status: ${candidate.status ?? '-'} | Score: ${candidate.score_total ?? candidate.score ?? 0}</div>
                                    <div>ETA: ${candidate.eta_seconds ?? 'n/a'}s | Distance: ${candidate.distance_meters ?? 'n/a'}m</div>
                                    ${reason ? `<div class="text-red-600">Reason: ${reason}</div>` : ''}
                                </div>
                                <div class="flex flex-wrap gap-1 justify-end">${selectedBadge}${recommendedBadge}${eligibleBadge}</div>
                            </div>
                            ${renderChecks(candidate)}
                            ${renderModifiers(candidate)}
                            ${renderEtaDiff(candidate)}
                            ${renderRuntime(candidate)}
                            <div class="flex flex-wrap gap-1">
                                <button ${eligible ? '' : 'disabled'} data-action="manual-dispatch" data-job-id="${job.id}" data-executor-id="${candidate.executor_id}" class="px-2 py-1 text-xs rounded bg-blue-600 text-white ${disabledClass}">Assign</button>
                                <button ${eligible ? '' : 'disabled'} data-action="manual-reassign" data-job-id="${job.id}" data-executor-id="${candidate.executor_id}" class="px-2 py-1 text-xs rounded bg-orange-600 text-white ${disabledClass}">Reassign</button>
                                <button data-action="open-executor" data-executor-id="${candidate.executor_id}" class="px-2 py-1 text-xs rounded border">Open executor</button>
                                <button data-action="center-executor" data-executor-id="${candidate.executor_id}" class="px-2 py-1 text-xs rounded border">Center on map</button>
                                <a href="/admin/service-jobs/${job.id}" target="_blank" class="px-2 py-1 text-xs rounded border">Open current job</a>
                            </div>
                        </div>
                    `;
                };

                const statusLine = selectedExecutorId && recommendedExecutorId
                    ? (selectedExecutorId === recommendedExecutorId
                        ? '<div class="text-xs text-green-700">Selected executor matches recommendation.</div>'
                        : '<div class="text-xs text-amber-700">Selected executor differs from recommendation.</div>')
                    : '<div class="text-xs text-gray-500">Select candidates to compare side-by-side.</div>';

                compareRoot.innerHTML = `
                    <div class="space-y-2">
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Candidate compare</div>
                        ${statusLine}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            ${card(left, 'Left')}
                            ${card(right, 'Right')}
                        </div>
                    </div>
                `;

                if (etaDiffRoot) {
                    const leftRouting = payload?.eta_strategy_diff?.left || {};
                    const rightRouting = payload?.eta_strategy_diff?.right || {};
                    etaDiffRoot.innerHTML = `
                        <div class="rounded border border-slate-200 bg-slate-50 px-2 py-2">
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">ETA strategy diff (shadow)</div>
                            <div class="text-[11px] text-gray-700 mt-1">Left: H ${leftRouting.heuristic_eta_seconds ?? 'n/a'}s | R ${leftRouting.routing_eta_seconds ?? 'n/a'}s | Δ ${leftRouting.eta_delta_seconds ?? 'n/a'}s (${leftRouting.significance ?? 'unavailable'})</div>
                            <div class="text-[11px] text-gray-700">Right: H ${rightRouting.heuristic_eta_seconds ?? 'n/a'}s | R ${rightRouting.routing_eta_seconds ?? 'n/a'}s | Δ ${rightRouting.eta_delta_seconds ?? 'n/a'}s (${rightRouting.significance ?? 'unavailable'})</div>
                        </div>
                    `;
                }
            };

            const loadCandidateCompare = async (jobId) => {
                if (!compareRoot || !jobId) return;
                try {
                    const payload = await apiGet(endpoints.candidateCompare(jobId, state.compareLeftExecutorId, state.compareRightExecutorId));
                    renderCandidateCompare(payload);
                } catch (_) {
                    compareRoot.innerHTML = '<div class="text-xs text-gray-500">Candidate compare unavailable.</div>';
                    if (etaDiffRoot) {
                        etaDiffRoot.innerHTML = '<div class="text-xs text-gray-500">ETA strategy diff unavailable.</div>';
                    }
                }
            };

            const renderJobDrawer = (data) => {
                const job = data.job || {};
                const executor = data.executor || null;
                const timeline = data.timeline || [];
                const exceptionsList = data.exceptions || [];
                const candidates = data.dispatch_candidates || [];
                const runtimeRules = data?.runtime?.effective_rule_values || {};
                const roadside = data?.special_hints?.roadside || {};
                const moving = data?.special_hints?.moving || {};
                const replanRecommendations = Array.isArray(data?.replan_recommendations) ? data.replan_recommendations : [];

                const formatModifier = (modifier) => {
                    if (!modifier) return '';
                    return `${modifier.label ?? '-'}: ${modifier.formatted ?? modifier.value ?? 0}`;
                };

                const renderChecks = (candidate) => {
                    const shift = candidate.shift_fit || candidate.checks?.shift_fit || {};
                    const windowFit = candidate.time_window_fit || candidate.checks?.time_window_fit || {};
                    const capacity = candidate.capacity_fit || candidate.checks?.capacity_fit || {};
                    const shiftLine = shift.eligible === true
                        ? 'Shift: Pass'
                        : (shift.eligible === false ? `Shift: Fail (${reasonLabel(shift.reason ?? 'out_of_shift')})` : 'Shift: n/a');
                    const windowLine = windowFit.fits === true
                        ? `Window: Pass (${windowFit.risk ?? 'low'} risk)`
                        : (windowFit.fits === false ? `Window: Fail (${reasonLabel('time_window_miss')})` : 'Window: n/a');
                    const capacityLine = capacity.fits === true
                        ? 'Capacity: Pass'
                        : (capacity.fits === false ? `Capacity: Fail (${reasonLabel(capacity.reason ?? 'capacity_mismatch')})` : 'Capacity: n/a');

                    return `
                        <div class="text-[11px] text-gray-600 mt-1">
                            <div>${shiftLine}</div>
                            <div>${windowLine}</div>
                            <div>${capacityLine}</div>
                        </div>
                    `;
                };

                const renderModifiers = (candidate) => {
                    const modifiers = candidate.modifiers ? Object.values(candidate.modifiers) : [];
                    if (!modifiers.length) {
                        return '<div class="text-[11px] text-gray-500 mt-1">No modifiers.</div>';
                    }

                    return `
                        <div class="text-[11px] text-gray-600 mt-1">
                            ${modifiers.map((m) => `<div>${formatModifier(m)}</div>`).join('')}
                        </div>
                    `;
                };

                const candidateItems = candidates.slice(0, 6).map((c) => {
                    const selectedBadge = c.selected ? '<span class="px-1.5 py-0.5 text-[10px] rounded bg-green-100 text-green-700">Selected</span>' : '';
                    const eligibleBadge = (c.is_eligible ?? c.eligible)
                        ? '<span class="px-1.5 py-0.5 text-[10px] rounded bg-blue-100 text-blue-700">Eligible</span>'
                        : '<span class="px-1.5 py-0.5 text-[10px] rounded bg-red-100 text-red-700">Ineligible</span>';
                    const reasonLabel = c.rejection_reason_label || c.rejection_reason || '';
                    const roadsideBadge = c?.special_hints?.roadside_emergency_override_applied
                        ? '<span class="px-1.5 py-0.5 text-[10px] rounded bg-amber-100 text-amber-700">Emergency boost</span>'
                        : '';
                    const movingBadge = c?.special_hints?.moving_team_candidate
                        ? '<span class="px-1.5 py-0.5 text-[10px] rounded bg-violet-100 text-violet-700">Moving team</span>'
                        : '';

                    const canAct = !!(c.is_eligible ?? c.eligible);
                    const disabledClass = canAct ? '' : 'opacity-50 cursor-not-allowed';

                    return `
                        <div class="border rounded px-2 py-2 space-y-1">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <div class="font-medium">${c.executor_name ?? c.display_name ?? '-'}</div>
                                    <div class="text-[11px] text-gray-500">${c.status ?? '-'} | score ${c.score_total ?? c.score ?? 0} | ETA ${c.eta_seconds ?? 'n/a'}s</div>
                                    ${reasonLabel ? `<div class="text-[11px] text-red-600">Reason: ${reasonLabel}</div>` : ''}
                                </div>
                                <div class="flex flex-wrap justify-end gap-1">
                                    ${selectedBadge}
                                    ${eligibleBadge}
                                    ${roadsideBadge}
                                    ${movingBadge}
                                </div>
                            </div>
                            ${renderChecks(c)}
                            ${renderModifiers(c)}
                            <div class="text-[11px] text-gray-600 mt-1">
                                ${c?.routing?.routing_available
                                    ? `Heuristic ${c?.routing?.heuristic_eta_seconds ?? 'n/a'}s | Routing ${c?.routing?.routing_eta_seconds ?? 'n/a'}s | Δ ${c?.routing?.eta_delta_seconds ?? 'n/a'}s (${c?.routing?.significance ?? 'unavailable'})`
                                    : `Routing ETA unavailable${c?.routing?.routing_error ? ` (${c.routing.routing_error})` : ''}`
                                }
                            </div>
                            <div class="flex flex-wrap gap-1 pt-1">
                                <button ${canAct ? '' : 'disabled'} data-action="manual-dispatch" data-job-id="${job.id}" data-executor-id="${c.executor_id}" class="px-2 py-1 text-xs rounded bg-blue-600 text-white ${disabledClass}">Assign</button>
                                <button ${canAct ? '' : 'disabled'} data-action="manual-reassign" data-job-id="${job.id}" data-executor-id="${c.executor_id}" class="px-2 py-1 text-xs rounded bg-orange-600 text-white ${disabledClass}">Reassign</button>
                                <button data-action="compare-left" data-job-id="${job.id}" data-executor-id="${c.executor_id}" class="px-2 py-1 text-xs rounded border">Compare L</button>
                                <button data-action="compare-right" data-job-id="${job.id}" data-executor-id="${c.executor_id}" class="px-2 py-1 text-xs rounded border">Compare R</button>
                                <button data-action="open-executor" data-executor-id="${c.executor_id}" class="px-2 py-1 text-xs rounded border">Open executor</button>
                                <button data-action="center-executor" data-executor-id="${c.executor_id}" class="px-2 py-1 text-xs rounded border">Center map</button>
                                <a href="/admin/service-jobs/${job.id}" target="_blank" class="px-2 py-1 text-xs rounded border">Open current job</a>
                            </div>
                        </div>
                    `;
                }).join('');

                const runtimeBlock = `
                    <div class="space-y-1">
                        <div class="text-xs font-semibold">Effective runtime rules</div>
                        ${(runtimeRules && Object.keys(runtimeRules).length)
                            ? `<div class="text-[11px] text-gray-600">
                                <div>ETA weight: ${runtimeRules?.weights?.eta ?? 'n/a'}</div>
                                <div>Window weight: ${runtimeRules?.weights?.time_window_fit ?? 'n/a'}</div>
                                <div>Capacity weight: ${runtimeRules?.weights?.capacity_fit ?? 'n/a'}</div>
                                <div>Emergency boost: ${runtimeRules?.modifiers?.emergency_boost ?? 'n/a'}</div>
                                <div>High-risk penalty: ${runtimeRules?.modifiers?.window_high_risk_penalty ?? 'n/a'}</div>
                            </div>`
                            : '<div class="text-xs text-gray-500">No runtime rule overrides.</div>'
                        }
                    </div>
                `;

                const roadsideHint = roadside?.is_emergency
                    ? `
                        <div class="rounded border border-amber-300 bg-amber-50 px-2 py-1">
                            <div class="text-xs font-semibold text-amber-800">Roadside emergency fast-lane applied</div>
                            <div class="text-[11px] text-amber-800">Acceptance timeout: ${roadside?.acceptance_timeout_seconds ?? 'n/a'} sec</div>
                            <div class="text-[11px] text-amber-800">Preempted assignments: ${roadside?.preempted_assignments_count ?? 0}</div>
                        </div>
                    `
                    : '';

                const movingHint = (job?.domain === 'moving')
                    ? `
                        <div class="rounded border border-violet-300 bg-violet-50 px-2 py-1">
                            <div class="text-xs font-semibold text-violet-800">Moving team diagnostics</div>
                            <div class="text-[11px] text-violet-800">Required team size: ${moving?.required_team_size ?? 'n/a'}</div>
                            <div class="text-[11px] text-violet-800">Found members: ${moving?.team_size_found ?? 0}</div>
                            <div class="text-[11px] text-violet-800">Team ETA: ${moving?.team_eta_seconds ?? 'n/a'} sec</div>
                            <div class="text-[11px] text-violet-800">Lead executor: ${moving?.team_lead_executor_id ?? 'n/a'}</div>
                            ${moving?.team_candidate_found ? '' : '<div class="text-[11px] text-violet-800">No team candidate data.</div>'}
                        </div>
                    `
                    : '';

                drawer.innerHTML = `
                    <div class="space-y-3">
                        <div class="font-semibold">Job #${job.id}</div>
                        <div class="text-xs text-gray-600">${job.domain ?? '-'} | ${job.kind ?? '-'} | ${job.status_label ?? '-'}</div>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div><span class="text-gray-500">Priority:</span> ${job.priority ?? '-'}</div>
                            <div><span class="text-gray-500">ETA:</span> ${job.eta ?? '-'}</div>
                            <div><span class="text-gray-500">SLA:</span> ${job.sla_label ?? '-'}</div>
                            <div><span class="text-gray-500">Exceptions:</span> ${job.exceptions_count ?? 0}</div>
                            <div class="col-span-2"><span class="text-gray-500">Executor:</span> ${executor?.display_name ?? 'Unassigned'}</div>
                        </div>

                        ${roadsideHint}
                        ${movingHint}

                        <div class="space-y-1">
                            <div class="text-xs font-semibold">Candidate diagnostics</div>
                            ${candidateItems || '<div class="text-xs text-gray-500">No dispatch candidates yet.</div>'}
                        </div>

                        ${runtimeBlock}
                        <div class="space-y-1">
                            <div class="text-xs font-semibold">ETA strategy (shadow)</div>
                            ${(candidates.slice(0, 4).map((candidate) => `
                                <div class="text-[11px] border rounded px-2 py-1">
                                    ${candidate.executor_name ?? candidate.display_name ?? '-'}:
                                    H ${candidate?.routing?.heuristic_eta_seconds ?? 'n/a'}s /
                                    R ${candidate?.routing?.routing_eta_seconds ?? 'n/a'}s /
                                    Δ ${candidate?.routing?.eta_delta_seconds ?? 'n/a'}s
                                    (${candidate?.routing?.significance ?? 'unavailable'})
                                </div>
                            `).join('')) || '<div class="text-xs text-gray-500">No shadow ETA data.</div>'}
                        </div>

                        <div class="space-y-1">
                            <div class="text-xs font-semibold">Recent timeline</div>
                            ${(timeline.slice(0, 5).map((t) => `<div class="text-xs border rounded px-2 py-1">${t.event_type} | ${t.occurred_at ?? '-'}</div>`).join('')) || '<div class="text-xs text-gray-500">No timeline events.</div>'}
                        </div>

                        <div class="space-y-1">
                            <div class="text-xs font-semibold">Open exceptions</div>
                            ${(exceptionsList.slice(0, 5).map((e) => `<div class="text-xs border rounded px-2 py-1">${e.type} | ${e.severity} | ${e.status}</div>`).join('')) || '<div class="text-xs text-gray-500">No open exceptions.</div>'}
                        </div>

                        <div class="flex flex-wrap gap-2 pt-1">
                            <button data-action="bulk-action" data-bulk-action="exceptions_bulk_acknowledge" data-ids="${exceptionsList.map((e) => e.id).join(',')}" class="px-2 py-1 text-xs rounded border">Bulk acknowledge exceptions</button>
                            <button data-action="bulk-action" data-bulk-action="exceptions_bulk_resolve" data-ids="${exceptionsList.map((e) => e.id).join(',')}" class="px-2 py-1 text-xs rounded border">Bulk resolve exceptions</button>
                            <button data-action="bulk-action" data-bulk-action="jobs_bulk_reassign_request" data-ids="${job.id}" class="px-2 py-1 text-xs rounded border">Bulk reassign request</button>
                            <button data-action="bulk-action" data-bulk-action="jobs_bulk_assign_dispatcher_queue" data-ids="${job.id}" class="px-2 py-1 text-xs rounded border">Bulk assign to queue</button>
                            <button data-action="bulk-open-on-map" data-job-id="${job.id}" class="px-2 py-1 text-xs rounded border">Bulk open on map</button>
                        </div>

                        <div class="flex gap-2 pt-1">
                            <a href="/admin/service-jobs/${job.id}" target="_blank" class="px-2 py-1 text-xs rounded border">Open job</a>
                            <a href="/admin/operation-exceptions?tableFilters%5Bservice_job_id%5D%5Bvalue%5D=${job.id}" target="_blank" class="px-2 py-1 text-xs rounded border">Open exceptions</a>
                        </div>
                    </div>
                `;

                if (replanRoot) {
                    replanRoot.innerHTML = `
                        <div class="space-y-1 rounded border border-slate-200 bg-slate-50 px-2 py-2">
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Replan recommendations</div>
                            ${(replanRecommendations.length
                                ? replanRecommendations.slice(0, 6).map((item) => `
                                    <div class="text-[11px] border rounded px-2 py-1 bg-white">
                                        <div class="font-semibold">${item.type} (${item.severity})</div>
                                        <div>Job #${item.service_job_id} | Status: ${item.status}</div>
                                        <div>Detected: ${item.detected_at ?? '-'}</div>
                                        <div class="mt-1 flex gap-1">
                                            <button data-action="sticky-open-job" data-job-id="${item.service_job_id}" class="px-2 py-1 text-[11px] rounded border">Open on map</button>
                                            <button data-action="open-job-from-replan" data-job-id="${item.service_job_id}" class="px-2 py-1 text-[11px] rounded border">Compare</button>
                                        </div>
                                    </div>
                                `).join('')
                                : '<div class="text-xs text-gray-500">No open replan recommendations.</div>')
                            }
                        </div>
                    `;
                }
            };

            const renderExecutorDrawer = (data) => {
                const executor = data.executor || {};
                const assignment = data.active_assignment || null;
                const job = data.active_job || null;
                const location = data.last_location || null;

                drawer.innerHTML = `
                    <div class="space-y-3">
                        <div class="font-semibold">Executor #${executor.id}</div>
                        <div class="text-sm">${executor.display_name ?? '-'}</div>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div><span class="text-gray-500">Status:</span> ${executor.status_label ?? executor.status ?? '-'}</div>
                            <div><span class="text-gray-500">Vehicle:</span> ${executor.vehicle_type ?? '-'}</div>
                            <div><span class="text-gray-500">Last seen:</span> ${executor.last_seen_at ?? '-'}</div>
                            <div><span class="text-gray-500">GPS:</span> ${executor.stale ? 'Stale' : 'Fresh'}</div>
                        </div>
                        <div class="text-xs text-gray-600">Active assignment: ${assignment ? '#' + assignment.id + ' · ' + assignment.status : 'None'}</div>
                        <div class="text-xs text-gray-600">Active job: ${job ? '#' + job.id + ' · ' + job.status : 'None'}</div>
                        <div class="text-xs text-gray-600">Live coordinates: ${location ? `${location.latitude}, ${location.longitude}` : 'No live pings yet'}</div>
                        <div class="flex gap-2 pt-1">
                            <a href="/api/ops/executors/${executor.id}" target="_blank" class="px-2 py-1 text-xs rounded border">Open executor API</a>
                            ${job ? `<a href="/admin/service-jobs/${job.id}" target="_blank" class="px-2 py-1 text-xs rounded border">Open active job</a>` : ''}
                        </div>
                    </div>
                `;
            };

            const renderExceptionDrawer = (data) => {
                const ex = data.exception || {};
                const linkedJob = data.linked_job || null;

                drawer.innerHTML = `
                    <div class="space-y-3">
                        <div class="font-semibold">Exception #${ex.id}</div>
                        <div class="text-xs text-gray-600">${ex.type_label ?? ex.type ?? '-'} · ${ex.severity ?? '-'} · ${ex.status ?? '-'}</div>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div><span class="text-gray-500">Job:</span> ${ex.service_job_id ?? '-'}</div>
                            <div><span class="text-gray-500">Executor:</span> ${ex.executor_id ?? '-'}</div>
                            <div><span class="text-gray-500">Detected:</span> ${ex.detected_at ?? '-'}</div>
                            <div><span class="text-gray-500">Owner:</span> ${ex.owner_user_id ?? '-'}</div>
                        </div>
                        <div class="text-xs text-gray-600 break-all">Context: ${JSON.stringify(ex.context ?? {})}</div>
                        <div class="flex gap-2 pt-1">
                            <button data-action="ack-exception" data-exception-id="${ex.id}" class="px-2 py-1 text-xs rounded bg-amber-600 text-white">Acknowledge</button>
                            <button data-action="resolve-exception" data-exception-id="${ex.id}" class="px-2 py-1 text-xs rounded bg-green-600 text-white">Resolve</button>
                            ${linkedJob ? `<a href="/admin/service-jobs/${linkedJob.id}" target="_blank" class="px-2 py-1 text-xs rounded border">Open linked job</a>` : ''}
                        </div>
                    </div>
                `;
            };

            const loadDrawer = async (mode, id, silent = false) => {
                state.drawerMode = mode;
                if (!silent) {
                    setNotice('Loading...');
                }
                try {
                    if (mode === 'job') {
                        state.selectedJobId = id;
                        state.drawerData = await apiGet(endpoints.jobDrawer(id));
                        renderJobDrawer(state.drawerData);
                        await loadCandidateCompare(Number(id));
                        await loadReplanRecommendations(Number(id));
                    } else if (mode === 'executor') {
                        state.selectedExecutorId = id;
                        state.drawerData = await apiGet(endpoints.executorDrawer(id));
                        renderExecutorDrawer(state.drawerData);
                    } else if (mode === 'exception') {
                        state.selectedExceptionId = id;
                        state.drawerData = await apiGet(endpoints.exceptionDrawer(id));
                        renderExceptionDrawer(state.drawerData);
                    }
                } catch (e) {
                    setNotice(`Failed to load drawer: ${e.message}`);
                }
            };

            const loadReplanRecommendations = async (serviceJobId = null) => {
                if (!replanRoot) return;
                try {
                    const suffix = serviceJobId ? `?service_job_id=${serviceJobId}` : '';
                    const payload = await apiGet(endpoints.replanRecommendations() + suffix);
                    const items = Array.isArray(payload?.items) ? payload.items : [];
                    if (!items.length) {
                        replanRoot.innerHTML = '<div class="text-xs text-gray-500">No open replan recommendations.</div>';
                        return;
                    }
                    replanRoot.innerHTML = `
                        <div class="space-y-1 rounded border border-slate-200 bg-slate-50 px-2 py-2">
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Replan recommendations</div>
                            ${items.slice(0, 8).map((item) => `
                                <div class="text-[11px] border rounded px-2 py-1 bg-white">
                                    <div class="font-semibold">${item.type} (${item.severity})</div>
                                    <div>Job #${item.service_job_id} | ${item?.job?.service_domain ?? '-'}</div>
                                    <div class="mt-1 flex gap-1">
                                        <button data-action="sticky-open-job" data-job-id="${item.service_job_id}" class="px-2 py-1 text-[11px] rounded border">Open on map</button>
                                        <button data-action="open-job-from-replan" data-job-id="${item.service_job_id}" class="px-2 py-1 text-[11px] rounded border">Compare</button>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    `;
                } catch (_) {
                    replanRoot.innerHTML = '<div class="text-xs text-gray-500">Replan recommendations unavailable.</div>';
                }
            };

            const renderRoutingShadowMetrics = (payload, healthFallback = null) => {
                if (!routingShadowMetricsRoot) return;

                const health = payload?.provider_health ?? healthFallback ?? {};
                const metrics = payload?.metrics ?? {};
                const significance = metrics?.significance_distribution ?? {};
                const recommendations = metrics?.recommendations_by_type ?? {};
                const domains = Array.isArray(metrics?.breakdown_by_service_domain) ? metrics.breakdown_by_service_domain : [];
                const providerReachable = !!health?.reachable;
                const providerClass = providerReachable
                    ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                    : 'border-amber-200 bg-amber-50 text-amber-700';

                const recRows = Object.entries(recommendations);
                const recHtml = recRows.length
                    ? recRows.map(([type, count]) => `
                        <div class="flex items-center justify-between rounded border border-slate-200 bg-slate-50 px-2 py-1">
                            <span class="text-[11px]">${String(type).replace(/_/g, ' ')}</span>
                            <strong class="text-xs">${Number(count || 0)}</strong>
                        </div>
                    `).join('')
                    : '<div class="text-[11px] text-slate-500">No recommendations yet.</div>';

                const domainHtml = domains.length
                    ? domains.slice(0, 6).map((domain) => `
                        <tr class="border-b border-slate-100">
                            <td class="px-2 py-1 text-[11px]">${domain.service_domain ?? 'unknown'}</td>
                            <td class="px-2 py-1 text-right text-[11px]">${Number(domain.snapshots ?? 0)}</td>
                            <td class="px-2 py-1 text-right text-[11px]">${Number(domain.avg_eta_delta_seconds ?? 0)}</td>
                            <td class="px-2 py-1 text-right text-[11px]">${Number(domain.high_significance_count ?? 0)}</td>
                            <td class="px-2 py-1 text-right text-[11px]">${Number(domain.ranking_drift_count ?? 0)}</td>
                        </tr>
                    `).join('')
                    : '<tr><td colspan="5" class="px-2 py-2 text-[11px] text-slate-500">No domain breakdown yet.</td></tr>';

                routingShadowMetricsRoot.innerHTML = `
                    <div class="rounded border border-slate-200 bg-white px-3 py-2">
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">Routing shadow metrics</div>
                            <span class="text-[11px] px-2 py-1 rounded border ${providerClass}">
                                ${providerReachable ? 'Provider reachable' : 'Provider warn-only degraded'}
                            </span>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mb-2">
                            <div class="rounded border border-slate-200 bg-slate-50 px-2 py-1">
                                <div class="text-[11px] text-slate-500">Total snapshots</div>
                                <div class="text-sm font-semibold">${Number(metrics?.total_snapshots ?? 0)}</div>
                            </div>
                            <div class="rounded border border-slate-200 bg-slate-50 px-2 py-1">
                                <div class="text-[11px] text-slate-500">Avg delta (sec)</div>
                                <div class="text-sm font-semibold">${Number(metrics?.avg_eta_delta_seconds ?? 0)}</div>
                            </div>
                            <div class="rounded border border-slate-200 bg-slate-50 px-2 py-1">
                                <div class="text-[11px] text-slate-500">Avg delta (%)</div>
                                <div class="text-sm font-semibold">${Number(metrics?.avg_delta_percent ?? 0)}</div>
                            </div>
                            <div class="rounded border border-slate-200 bg-slate-50 px-2 py-1">
                                <div class="text-[11px] text-slate-500">Ranking drift</div>
                                <div class="text-sm font-semibold">${Number(metrics?.ranking_drift_count ?? 0)}</div>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2 mb-2">
                            <div class="rounded border border-slate-200 bg-slate-50 px-2 py-1">
                                <div class="text-[11px] text-slate-500 mb-1">Significance</div>
                                <div class="text-[11px]">Low: <strong>${Number(significance?.low ?? 0)}</strong></div>
                                <div class="text-[11px]">Medium: <strong>${Number(significance?.medium ?? 0)}</strong></div>
                                <div class="text-[11px]">High: <strong>${Number(significance?.high ?? 0)}</strong></div>
                            </div>
                            <div class="rounded border border-slate-200 bg-slate-50 px-2 py-1">
                                <div class="text-[11px] text-slate-500 mb-1">Provider health</div>
                                <div class="text-[11px]">Provider: <strong>${health?.provider ?? 'n/a'}</strong></div>
                                <div class="text-[11px]">Latency: <strong>${health?.latency_ms ?? 'n/a'} ms</strong></div>
                                <div class="text-[11px]">Error: <strong>${health?.error ?? '-'}</strong></div>
                            </div>
                            <div class="rounded border border-slate-200 bg-slate-50 px-2 py-1">
                                <div class="text-[11px] text-slate-500 mb-1">Recommendations</div>
                                <div class="space-y-1">${recHtml}</div>
                            </div>
                        </div>
                        <div class="rounded border border-slate-200 overflow-hidden">
                            <table class="w-full text-left">
                                <thead class="bg-slate-50 text-[11px] text-slate-500">
                                    <tr>
                                        <th class="px-2 py-1">Domain</th>
                                        <th class="px-2 py-1 text-right">Snapshots</th>
                                        <th class="px-2 py-1 text-right">Avg Δ sec</th>
                                        <th class="px-2 py-1 text-right">High sig</th>
                                        <th class="px-2 py-1 text-right">Drift</th>
                                    </tr>
                                </thead>
                                <tbody>${domainHtml}</tbody>
                            </table>
                        </div>
                    </div>
                `;
            };

            const loadRoutingShadowMetrics = async () => {
                if (!routingShadowMetricsRoot) return;
                try {
                    const payload = await apiGet(endpoints.routingShadowMetrics());
                    renderRoutingShadowMetrics(payload);
                } catch (_) {
                    try {
                        const health = await apiGet(endpoints.routingProviderHealth());
                        renderRoutingShadowMetrics(null, health);
                    } catch (_) {
                        routingShadowMetricsRoot.innerHTML = '<div class="text-xs text-gray-500">Routing shadow metrics unavailable.</div>';
                    }
                }
            };

            setInterval(() => {
                if (state.drawerMode === 'job' && state.selectedJobId) {
                    loadDrawer('job', state.selectedJobId, true);
                } else if (state.drawerMode === 'executor' && state.selectedExecutorId) {
                    loadDrawer('executor', state.selectedExecutorId, true);
                } else if (state.drawerMode === 'exception' && state.selectedExceptionId) {
                    loadDrawer('exception', state.selectedExceptionId, true);
                }
            }, 15000);

            drawer.addEventListener('click', async (event) => {
                const button = event.target.closest('button[data-action]');
                if (!button) return;

                const action = button.dataset.action;
                try {
                    if (action === 'manual-dispatch' || action === 'manual-reassign') {
                        const jobId = button.dataset.jobId;
                        const executorId = button.dataset.executorId;
                        const reason = prompt('Reason (optional):', '') || null;
                        const endpoint = action === 'manual-dispatch'
                            ? endpoints.manualDispatch(jobId)
                            : endpoints.manualReassign(jobId);
                        await apiPost(endpoint, {
                            executor_id: Number(executorId),
                            notes: reason,
                            reason: reason,
                            expected_job_version: state.drawerData?.drawer_version ?? null,
                        }, `${action}:${jobId}:${executorId}`);
                        await loadDrawer('job', Number(jobId));
                        await loadCandidateCompare(Number(jobId));
                        await refreshWorkbenchSignals();
                        return;
                    }

                    if (action === 'compare-left' || action === 'compare-right') {
                        const jobId = Number(button.dataset.jobId);
                        const executorId = Number(button.dataset.executorId);
                        if (action === 'compare-left') {
                            state.compareLeftExecutorId = executorId;
                        } else {
                            state.compareRightExecutorId = executorId;
                        }
                        await loadCandidateCompare(jobId);
                        return;
                    }

                    if (action === 'open-executor') {
                        const executorId = Number(button.dataset.executorId);
                        await loadDrawer('executor', executorId);
                        return;
                    }

                    if (action === 'center-executor') {
                        const executorId = Number(button.dataset.executorId);
                        const executor = executors.find((item) => Number(item.id) === executorId);
                        const lat = executor?.last_location?.latitude;
                        const lng = executor?.last_location?.longitude;
                        if (map && lat && lng) {
                            map.setView([lat, lng], 14);
                        }
                        return;
                    }

                    if (action === 'bulk-open-on-map') {
                        const jobId = Number(button.dataset.jobId);
                        const jobItem = jobs.find((item) => Number(item.id) === jobId);
                        const coord = jobItem?.coordinates?.service?.lat ? jobItem.coordinates.service
                            : (jobItem?.coordinates?.pickup?.lat ? jobItem.coordinates.pickup : null);
                        if (coord?.lat && coord?.lng && map) {
                            map.setView([coord.lat, coord.lng], 14);
                        }
                        if (jobId > 0) {
                            await loadDrawer('job', jobId);
                        }
                        return;
                    }

                    if (action === 'bulk-action') {
                        const bulkAction = button.dataset.bulkAction;
                        const ids = (button.dataset.ids || '')
                            .split(',')
                            .map((id) => Number(id))
                            .filter((id) => Number.isFinite(id) && id > 0);
                        if (!bulkAction || ids.length === 0) return;
                        await apiPost(endpoints.bulkAction(), {
                            action: bulkAction,
                            ids,
                        }, `bulk:${bulkAction}:${ids.join('-')}`);
                        if (state.drawerMode === 'job' && state.selectedJobId) {
                            await loadDrawer('job', state.selectedJobId, true);
                        }
                        await refreshWorkbenchSignals();
                        return;
                    }

                    if (action === 'ack-exception') {
                        const exceptionId = button.dataset.exceptionId;
                        await apiPost(endpoints.exceptionAcknowledge(exceptionId), {
                            expected_exception_version: state.drawerData?.drawer_version ?? null,
                        }, `ack:${exceptionId}`);
                        await loadDrawer('exception', Number(exceptionId));
                        await refreshWorkbenchSignals();
                        return;
                    }

                    if (action === 'resolve-exception') {
                        const exceptionId = button.dataset.exceptionId;
                        const resolutionCode = prompt('Resolution code:', 'manual_resolution') || '';
                        if (!resolutionCode) return;
                        const resolutionNotes = prompt('Resolution notes (optional):', '') || null;
                        await apiPost(endpoints.exceptionResolve(exceptionId), {
                            resolution_code: resolutionCode,
                            resolution_notes: resolutionNotes,
                            expected_exception_version: state.drawerData?.drawer_version ?? null,
                        }, `resolve:${exceptionId}`);
                        await loadDrawer('exception', Number(exceptionId));
                        await refreshWorkbenchSignals();
                    }
                } catch (e) {
                    if (state.drawerMode === 'job' && state.selectedJobId) {
                        loadDrawer('job', state.selectedJobId, true);
                    } else if (state.drawerMode === 'exception' && state.selectedExceptionId) {
                        loadDrawer('exception', state.selectedExceptionId, true);
                    }
                    alert(`Action failed: ${e.message}`);
                }
            });

            compareRoot?.addEventListener('click', async (event) => {
                const button = event.target.closest('button[data-action]');
                if (!button) return;

                const action = button.dataset.action;
                try {
                    if (action === 'manual-dispatch' || action === 'manual-reassign') {
                        const jobId = button.dataset.jobId;
                        const executorId = button.dataset.executorId;
                        const reason = prompt('Reason (optional):', '') || null;
                        const endpoint = action === 'manual-dispatch'
                            ? endpoints.manualDispatch(jobId)
                            : endpoints.manualReassign(jobId);
                        await apiPost(endpoint, {
                            executor_id: Number(executorId),
                            notes: reason,
                            reason: reason,
                            expected_job_version: state.drawerData?.drawer_version ?? null,
                        }, `${action}:${jobId}:${executorId}`);
                        await loadDrawer('job', Number(jobId));
                        await loadCandidateCompare(Number(jobId));
                        await refreshWorkbenchSignals();
                        return;
                    }

                    if (action === 'open-executor') {
                        const executorId = Number(button.dataset.executorId);
                        await loadDrawer('executor', executorId);
                        return;
                    }

                    if (action === 'center-executor') {
                        const executorId = Number(button.dataset.executorId);
                        const executor = executors.find((item) => Number(item.id) === executorId);
                        const lat = executor?.last_location?.latitude;
                        const lng = executor?.last_location?.longitude;
                        if (map && lat && lng) {
                            map.setView([lat, lng], 14);
                        }
                    }
                } catch (e) {
                    alert(`Compare action failed: ${e.message}`);
                }
            });

            stickyRoot?.addEventListener('click', async (event) => {
                const button = event.target.closest('button[data-action]');
                if (!button) return;

                const action = button.dataset.action;
                if (action === 'sticky-filter') {
                    try {
                        const filters = JSON.parse(button.dataset.filter || '{}');
                        applyFiltersToUi(filters);
                    } catch (_) {
                        // ignore invalid payload
                    }
                    return;
                }

                if (action === 'sticky-open-job') {
                    const jobId = Number(button.dataset.jobId);
                    if (jobId > 0) {
                        await loadDrawer('job', jobId);
                    }
                    return;
                }

                if (action === 'open-job-from-replan') {
                    const jobId = Number(button.dataset.jobId);
                    if (jobId > 0) {
                        await loadDrawer('job', jobId);
                    }
                }
            });

            replanRoot?.addEventListener('click', async (event) => {
                const button = event.target.closest('button[data-action]');
                if (!button) return;
                const action = button.dataset.action;
                if (!['sticky-open-job', 'open-job-from-replan'].includes(action)) return;
                const jobId = Number(button.dataset.jobId);
                if (jobId > 0) {
                    await loadDrawer('job', jobId);
                }
            });

            latencyRoot?.addEventListener('click', async (event) => {
                const button = event.target.closest('button[data-action]');
                if (!button) return;

                const action = button.dataset.action;
                if (action === 'latency-filter') {
                    try {
                        const filters = JSON.parse(button.dataset.filter || '{}');
                        applyFiltersToUi(filters);
                    } catch (_) {
                        // ignore invalid payload
                    }
                    return;
                }

                if (action === 'latency-open-job') {
                    const jobId = Number(button.dataset.jobId);
                    if (jobId > 0) {
                        await loadDrawer('job', jobId);
                    }
                }
            });

            bulkPanelRoot?.addEventListener('click', async (event) => {
                const button = event.target.closest('button[data-action]');
                if (!button) return;

                const feedback = document.getElementById('bulk-triage-feedback');
                const exceptionInput = document.getElementById('bulk-exception-ids');
                const jobInput = document.getElementById('bulk-job-ids');
                const action = button.dataset.action;

                try {
                    if (action === 'bulk-panel-use-current') {
                        const currentJobId = Number(state.drawerData?.job?.id || state.selectedJobId || 0);
                        const exceptionIds = (state.drawerData?.exceptions || [])
                            .map((item) => item.id)
                            .filter((id) => Number.isFinite(Number(id)));
                        if (jobInput) {
                            jobInput.value = currentJobId > 0 ? String(currentJobId) : '';
                        }
                        if (exceptionInput) {
                            exceptionInput.value = exceptionIds.join(',');
                        }
                        if (feedback) {
                            feedback.textContent = 'Prefilled from current drawer.';
                        }
                        return;
                    }

                    if (action === 'bulk-panel-open-map') {
                        const ids = parseIdList(jobInput?.value || '');
                        if (!ids.length) {
                            if (feedback) feedback.textContent = 'Enter job IDs first.';
                            return;
                        }
                        const jobId = ids[0];
                        const jobItem = jobs.find((item) => Number(item.id) === Number(jobId));
                        const coord = jobItem?.coordinates?.service?.lat ? jobItem.coordinates.service
                            : (jobItem?.coordinates?.pickup?.lat ? jobItem.coordinates.pickup : null);
                        if (coord?.lat && coord?.lng && map) {
                            map.setView([coord.lat, coord.lng], 14);
                        }
                        await loadDrawer('job', Number(jobId));
                        if (feedback) feedback.textContent = `Opened job #${jobId} on map.`;
                        return;
                    }

                    if (action === 'bulk-panel-action') {
                        const bulkAction = button.dataset.bulkAction;
                        const useExceptionIds = bulkAction?.startsWith('exceptions_');
                        const ids = useExceptionIds ? parseIdList(exceptionInput?.value || '') : parseIdList(jobInput?.value || '');
                        if (!bulkAction || !ids.length) {
                            if (feedback) feedback.textContent = 'Provide IDs before running bulk action.';
                            return;
                        }
                        const result = await apiPost(endpoints.bulkAction(), {
                            action: bulkAction,
                            ids,
                        }, `bulk-panel:${bulkAction}:${ids.join('-')}`);
                        if (feedback) {
                            feedback.textContent = `${result.message ?? 'Bulk action completed'} (${result.updated_count ?? 0}).`;
                        }
                        await refreshWorkbenchSignals();
                        if (state.drawerMode === 'job' && state.selectedJobId) {
                            await loadDrawer('job', state.selectedJobId, true);
                            await loadCandidateCompare(state.selectedJobId);
                        }
                        return;
                    }
                } catch (error) {
                    if (feedback) {
                        feedback.textContent = `Bulk triage failed: ${error.message}`;
                    }
                }
            });

            triageRoot?.addEventListener('click', async (event) => {
                const button = event.target.closest('button[data-action="triage-filter"]');
                if (!button) return;
                try {
                    const filters = JSON.parse(button.dataset.filter || '{}');
                    applyFiltersToUi(filters);
                } catch (_) {
                    // ignore broken payload
                }
            });

            savedFiltersRoot?.addEventListener('click', async (event) => {
                const button = event.target.closest('button[data-action]');
                if (!button) return;
                const action = button.dataset.action;
                try {
                    if (action === 'save-current-filter') {
                        const name = prompt('Preset name:', 'Dispatcher preset') || '';
                        if (!name.trim()) return;
                        await apiPost(endpoints.savedFilters(), {
                            name: name.trim(),
                            filters: collectCurrentFilters(),
                        }, `save-filter:${name.trim()}`);
                        await loadSavedFilters();
                        return;
                    }

                    if (action === 'apply-saved-filter') {
                        const filters = JSON.parse(button.dataset.filters || '{}');
                        applyFiltersToUi(filters);
                        return;
                    }

                    if (action === 'delete-saved-filter') {
                        const filterId = Number(button.dataset.filterId);
                        if (!filterId) return;
                        await fetch(`/api/ops/workbench/saved-filters/${filterId}`, {
                            method: 'DELETE',
                            credentials: 'include',
                            headers: {
                                'X-CSRF-TOKEN': csrf,
                                Accept: 'application/json',
                            },
                        });
                        await loadSavedFilters();
                    }
                } catch (e) {
                    alert(`Saved filter action failed: ${e.message}`);
                }
            });

            if (window.Echo && organizationId) {
                try {
                    window.Echo.private(`ops.organization.${organizationId}`)
                        .listen('.service_job.updated', () => {
                            if (state.drawerMode === 'job' && state.selectedJobId) {
                                loadDrawer('job', state.selectedJobId, true);
                            }
                        })
                        .listen('.operation_exception.updated', () => {
                            if (state.drawerMode === 'exception' && state.selectedExceptionId) {
                                loadDrawer('exception', state.selectedExceptionId, true);
                            }
                            if (state.drawerMode === 'job' && state.selectedJobId) {
                                loadDrawer('job', state.selectedJobId, true);
                            }
                        })
                        .listen('.executor.location.updated', () => {
                            if (state.drawerMode === 'executor' && state.selectedExecutorId) {
                                loadDrawer('executor', state.selectedExecutorId, true);
                            }
                        })
                        .listen('.workbench.action.performed', () => {
                            if (state.drawerMode === 'job' && state.selectedJobId) {
                                loadDrawer('job', state.selectedJobId, true);
                            } else if (state.drawerMode === 'exception' && state.selectedExceptionId) {
                                loadDrawer('exception', state.selectedExceptionId, true);
                            } else if (state.drawerMode === 'executor' && state.selectedExecutorId) {
                                loadDrawer('executor', state.selectedExecutorId, true);
                            }
                        });
                } catch (_) {
                    // keep polling fallback
                }
            }

            map = L.map('ops-map').setView([59.9139, 10.7522], 11);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            const colorBySla = (state) => {
                if (state === 'breached') return '#dc2626';
                if (state === 'warning') return '#f59e0b';
                return '#16a34a';
            };

            let jobMarkers = 0;
            jobs.forEach((job) => {
                const coord = job.coordinates?.service?.lat ? job.coordinates.service
                    : (job.coordinates?.pickup?.lat ? job.coordinates.pickup : null);
                if (!coord || !coord.lat || !coord.lng) return;
                jobMarkers++;

                const marker = L.circleMarker([coord.lat, coord.lng], {
                    radius: 8,
                    color: colorBySla(job.sla_state),
                    fillOpacity: 0.8,
                }).addTo(map);

                marker.bindTooltip(`Job #${job.id} • ${job.status_label}`);
                marker.on('click', () => loadDrawer('job', Number(job.id)));
            });

            let executorMarkers = 0;
            executors.forEach((executor) => {
                const point = executor.last_location;
                if (!point || !point.latitude || !point.longitude) return;
                executorMarkers++;

                const markerColor = executor.stale_gps ? '#7f1d1d' : '#2563eb';
                const marker = L.circleMarker([point.latitude, point.longitude], {
                    radius: executor.stale_gps ? 10 : 7,
                    color: markerColor,
                    fillOpacity: 0.9,
                }).addTo(map);

                marker.bindTooltip(`${executor.display_name} • ${executor.status}${executor.stale_gps ? ' • stale GPS' : ''}`);
                marker.on('click', () => loadDrawer('executor', Number(executor.id)));
            });

            let exceptionMarkers = 0;
            exceptions.slice(0, 50).forEach((exception) => {
                const related = jobs.find((j) => j.id === exception.job_id);
                const coord = related?.coordinates?.service?.lat ? related.coordinates.service : related?.coordinates?.pickup;
                if (!coord || !coord.lat || !coord.lng) return;
                exceptionMarkers++;

                const marker = L.circleMarker([coord.lat, coord.lng], {
                    radius: 6,
                    color: '#991b1b',
                    fillOpacity: 1,
                }).addTo(map);
                marker.bindTooltip(`Exception #${exception.id} • ${exception.type_label ?? exception.type}`);
                marker.on('click', () => loadDrawer('exception', Number(exception.id)));
            });

            if (jobMarkers === 0 && executorMarkers === 0 && exceptionMarkers === 0) {
                setNotice('No active coordinates. Waiting for first GPS ping.');
            } else if (jobMarkers === 0) {
                setNotice('No active jobs on map for current filter.');
            } else if (executorMarkers === 0) {
                setNotice('No live pings yet. Awaiting executor telemetry.');
            } else if (exceptionMarkers === 0) {
                setNotice('No open exceptions in current filter.');
            }

            renderBulkTriagePanel();
            loadStickyIncidentBanner();
            loadLatencyStrip();
            loadTriageStrip();
            loadSavedFilters();
            loadReplanRecommendations();
            loadRoutingShadowMetrics();
        })();
    </script>
    </div>
</x-filament::page>

