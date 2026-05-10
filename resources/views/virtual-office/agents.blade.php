@extends('virtual-office.index')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Agents</h1>
                        <p class="text-sm text-gray-500">Manage all virtual office agents</p>
                    </div>
                    <a href="{{ route('virtual-office.canvas') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        View Canvas
                    </a>
                </div>

                <!-- Filters -->
                <div class="mb-6 flex items-center space-x-4">
                    <input type="text" id="search" placeholder="Search agents..."
                        class="w-64 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <select id="category-filter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">All Categories</option>
                        @foreach(\App\Models\VirtualOffice\Category::all() as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <select id="status-filter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="busy">Busy</option>
                        <option value="idle">Idle</option>
                        <option value="offline">Offline</option>
                    </select>
                </div>

                <!-- Agents Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach(\App\Models\VirtualOffice\Agent::with(['category', 'currentZone', 'tasks'])->get() as $agent)
                        <div class="agent-card bg-gray-50 rounded-lg p-6 hover:shadow-lg transition">
                            <div class="flex items-start space-x-4">
                                <div class="w-16 h-16 rounded-full flex items-center justify-center text-white text-2xl font-bold"
                                    style="background-color: {{ $agent->category->color ?? '#6B7280' }}">
                                    {{ substr($agent->name, 0, 2) }}
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-lg font-bold text-gray-900">{{ $agent->name }}</h3>
                                    <p class="text-sm text-gray-600">{{ $agent->role }}</p>
                                    <div class="flex items-center space-x-2 mt-2">
                                        <span class="px-2 py-1 text-xs rounded-full
                                            {{ $agent->status === 'active' ? 'bg-green-100 text-green-800' : '' }}
                                            {{ $agent->status === 'busy' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                            {{ $agent->status === 'idle' ? 'bg-blue-100 text-blue-800' : '' }}
                                            {{ $agent->status === 'offline' ? 'bg-gray-100 text-gray-800' : '' }}">
                                            {{ ucfirst($agent->status) }}
                                        </span>
                                        <span class="text-xs text-gray-500">{{ $agent->category->name ?? 'No Category' }}</span>
                                    </div>
                                </div>
                            </div>

                            @if($agent->description)
                                <p class="mt-4 text-sm text-gray-600">{{ Str::limit($agent->description, 100) }}</p>
                            @endif

                            @if($agent->skills)
                                <div class="mt-4 flex flex-wrap gap-2">
                                    @foreach(array_slice($agent->skills, 0, 3) as $skill)
                                        <span class="px-2 py-1 bg-gray-200 rounded text-xs text-gray-700">{{ $skill }}</span>
                                    @endforeach
                                    @if(count($agent->skills) > 3)
                                        <span class="px-2 py-1 bg-gray-200 rounded text-xs text-gray-700">+{{ count($agent->skills) - 3 }} more</span>
                                    @endif
                                </div>
                            @endif

                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500">Zone: {{ $agent->currentZone->name ?? 'Not assigned' }}</span>
                                    <span class="text-gray-500">Tasks: {{ $agent->tasks->count() }}</span>
                                </div>
                            </div>

                            <div class="mt-4">
                                <a href="{{ route('virtual-office.agents.show', $agent) }}"
                                    class="block w-full text-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                    View Details
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
