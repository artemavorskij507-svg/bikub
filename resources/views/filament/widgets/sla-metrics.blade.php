<div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5">
    <div class="mb-4 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-950">📊 SLA Metrics & Performance</h3>
        <button 
            wire:click="toggleCollapse"
            class="text-gray-500 hover:text-gray-700 transition"
        >
            @if($isCollapsed)
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            @else
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                </svg>
            @endif
        </button>
    </div>
    
    @unless($isCollapsed)
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Total Orders -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Orders (30d)</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $totalOrders }}</p>
                    </div>
                </div>
            </div>

            <!-- Breached Orders -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Breached Orders</p>
                        <p class="text-2xl font-semibold text-red-600">{{ $breachedOrders }}</p>
                    </div>
                </div>
            </div>

            <!-- At Risk Orders -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">At Risk Orders</p>
                        <p class="text-2xl font-semibold text-yellow-600">{{ $atRiskOrders }}</p>
                    </div>
                </div>
            </div>

            <!-- Breach Rate -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Breach Rate</p>
                        <p class="text-2xl font-semibold {{ $breachRate > 10 ? 'text-red-600' : ($breachRate > 5 ? 'text-yellow-600' : 'text-green-600') }}">{{ $breachRate }}%</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Metrics -->
        <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Average Breach Time -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Average Breach Time</h3>
                <div class="flex items-center">
                    <div class="text-3xl font-bold text-gray-900">{{ $averageBreachTime }}</div>
                    <div class="ml-2 text-sm text-gray-500">minutes</div>
                </div>
                <p class="text-sm text-gray-500 mt-2">Average time orders breach SLA deadline</p>
            </div>

            <!-- Weather Impact -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Weather Impact</h3>
                <div class="space-y-2">
                    @foreach($weatherImpact as $condition => $data)
                        @if($data['orders'] > 0)
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-700 capitalize">{{ $condition }}</span>
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm text-gray-500">{{ $data['orders'] }} orders</span>
                                    @if($data['breaches'] > 0)
                                        <span class="text-sm text-red-600">{{ $data['breaches'] }} breaches</span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        <!-- SLA Alerts -->
        @if($atRiskOrders > 0)
            <div class="mt-8 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">
                            SLA Alert
                        </h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <p>{{ $atRiskOrders }} orders are at risk of breaching SLA. Review dispatch queue and assign couriers immediately.</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endunless
</div>
