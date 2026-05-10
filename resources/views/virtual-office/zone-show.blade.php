@extends('virtual-office.index')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <!-- Back Button -->
                <div class="mb-6">
                    <a href="{{ route('virtual-office.zones') }}" class="text-blue-600 hover:text-blue-700 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Back to Zones
                    </a>
                </div>

                <!-- Zone Header -->
                <div class="flex items-start space-x-6 mb-8">
                    <div class="w-32 h-32 rounded-lg flex items-center justify-center"
                        style="background-color: {{ $zone->color ?? '#3B82F6' }}20">
                        <div class="w-20 h-20 rounded-full" style="background-color: {{ $zone->color ?? '#3B82F6' }}"></div>
                    </div>
                    <div class="flex-1">
                        <h1 class="text-3xl font-bold text-gray-900">{{ $zone->name }}</h1>
                        @if($zone->description)
                            <p class="text-xl text-gray-600 mt-2">{{ $zone->description }}</p>
                        @endif
                        <div class="flex items-center space-x-4 mt-3">
                            <span class="px-4 py-2 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                                {{ $zone->agents_count }} agents
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Zone Details -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Main Info -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Zone Properties -->
                        <div class="bg-gray-50 rounded-lg p-6">
                            <h2 class="text-lg font-semibold text-gray-900 mb-3">Zone Properties</h2>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Width</label>
                                    <div class="text-2xl font-bold text-gray-900">{{ $zone->width ?? 200 }}px</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Height</label>
                                    <div class="text-2xl font-bold text-gray-900">{{ $zone->height ?? 150 }}px</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">X Position</label>
                                    <div class="text-2xl font-bold text-gray-900">{{ $zone->x ?? 50 }}px</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Y Position</label>
                                    <div class="text-2xl font-bold text-gray-900">{{ $zone->y ?? 50 }}px</div>
                                </div>
                            </div>
                        </div>

                        <!-- Agents in Zone -->
                        <div class="bg-gray-50 rounded-lg p-6">
                            <h2 class="text-lg font-semibold text-gray-900 mb-3">Agents in this Zone ({{ $zone->agents->count() }})</h2>
                            @if($zone->agents->count() > 0)
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    @foreach($zone->agents as $agent)
                                        <div class="bg-white rounded-lg p-4 shadow-sm">
                                            <div class="flex items-center space-x-3">
                                                <div class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold"
                                                    style="background-color: {{ $agent->category->color ?? '#6B7280' }}">
                                                    {{ substr($agent->name, 0, 2) }}
                                                </div>
                                                <div class="flex-1">
                                                    <h3 class="font-medium text-gray-900">{{ $agent->name }}</h3>
                                                    <p class="text-sm text-gray-600">{{ $agent->role }}</p>
                                                </div>
                                                <span class="px-2 py-1 text-xs rounded-full
                                                    {{ $agent->status === 'active' ? 'bg-green-100 text-green-800' : '' }}
                                                    {{ $agent->status === 'busy' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                    {{ $agent->status === 'idle' ? 'bg-blue-100 text-blue-800' : '' }}
                                                    {{ $agent->status === 'offline' ? 'bg-gray-100 text-gray-800' : '' }}">
                                                    {{ ucfirst($agent->status) }}
                                                </span>
                                            </div>
                                            <div class="mt-3">
                                                <a href="{{ route('virtual-office.agents.show', $agent) }}"
                                                    class="text-blue-600 hover:text-blue-700 text-sm">View Details →</a>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gray-500">No agents in this zone</p>
                            @endif
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Quick Stats -->
                        <div class="bg-gray-50 rounded-lg p-6">
                            <h2 class="text-lg font-semibold text-gray-900 mb-3">Quick Stats</h2>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600">Total Agents</span>
                                    <span class="font-semibold text-gray-900">{{ $zone->agents->count() }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600">Active</span>
                                    <span class="font-semibold text-green-600">{{ $zone->agents->where('status', 'active')->count() }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600">Busy</span>
                                    <span class="font-semibold text-yellow-600">{{ $zone->agents->where('status', 'busy')->count() }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600">Idle</span>
                                    <span class="font-semibold text-blue-600">{{ $zone->agents->where('status', 'idle')->count() }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600">Offline</span>
                                    <span class="font-semibold text-gray-600">{{ $zone->agents->where('status', 'offline')->count() }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="bg-gray-50 rounded-lg p-6">
                            <h2 class="text-lg font-semibold text-gray-900 mb-3">Actions</h2>
                            <div class="space-y-3">
                                <a href="{{ route('virtual-office.canvas') }}"
                                    class="block w-full text-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                    View on Canvas
                                </a>
                                <a href="{{ route('virtual-office.agents') }}"
                                    class="block w-full text-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                                    View All Agents
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
