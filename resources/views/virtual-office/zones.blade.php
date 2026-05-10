@extends('virtual-office.index')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Office Zones</h1>
                        <p class="text-sm text-gray-500">Manage all virtual office zones</p>
                    </div>
                    <a href="{{ route('virtual-office.canvas') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        View Canvas
                    </a>
                </div>

                <!-- Zones Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach(\App\Models\VirtualOffice\OfficeZone::withCount('agents')->get() as $zone)
                        <div class="zone-card bg-gray-50 rounded-lg overflow-hidden hover:shadow-lg transition">
                            <!-- Zone Header -->
                            <div class="h-32 relative" style="background-color: {{ $zone->color ?? '#3B82F6' }}20">
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <div class="w-16 h-16 rounded-full" style="background-color: {{ $zone->color ?? '#3B82F6' }}"></div>
                                </div>
                                <div class="absolute top-4 right-4">
                                    <span class="px-3 py-1 bg-white/90 rounded-full text-sm font-medium text-gray-900">
                                        {{ $zone->agents_count }} agents
                                    </span>
                                </div>
                            </div>

                            <!-- Zone Content -->
                            <div class="p-6">
                                <h3 class="text-xl font-bold text-gray-900 mb-2">{{ $zone->name }}</h3>
                                @if($zone->description)
                                    <p class="text-gray-600 mb-4">{{ Str::limit($zone->description, 100) }}</p>
                                @endif

                                <!-- Zone Stats -->
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-gray-900">{{ $zone->width ?? 200 }}</div>
                                        <div class="text-xs text-gray-500">Width</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-2xl font-bold text-gray-900">{{ $zone->height ?? 150 }}</div>
                                        <div class="text-xs text-gray-500">Height</div>
                                    </div>
                                </div>

                                <!-- Zone Position -->
                                <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
                                    <span>X: {{ $zone->x ?? 50 }}</span>
                                    <span>Y: {{ $zone->y ?? 50 }}</span>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center space-x-3">
                                    <a href="{{ route('virtual-office.zones.show', $zone) }}"
                                        class="flex-1 text-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                        View Details
                                    </a>
                                    <a href="{{ route('virtual-office.canvas') }}"
                                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
