@extends('virtual-office.index')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <!-- Back Button -->
                <div class="mb-6">
                    <a href="{{ route('virtual-office.agents') }}" class="text-blue-600 hover:text-blue-700 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Back to Agents
                    </a>
                </div>

                <!-- Agent Header -->
                <div class="flex items-start space-x-6 mb-8">
                    <div class="w-32 h-32 rounded-full flex items-center justify-center text-white text-5xl font-bold"
                        style="background-color: {{ $agent->category->color ?? '#6B7280' }}">
                        {{ substr($agent->name, 0, 2) }}
                    </div>
                    <div class="flex-1">
                        <h1 class="text-3xl font-bold text-gray-900">{{ $agent->name }}</h1>
                        <p class="text-xl text-gray-600 mt-1">{{ $agent->role }}</p>
                        <div class="flex items-center space-x-4 mt-3">
                            <span class="px-4 py-2 rounded-full text-sm font-medium
                                {{ $agent->status === 'active' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $agent->status === 'busy' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $agent->status === 'idle' ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $agent->status === 'offline' ? 'bg-gray-100 text-gray-800' : '' }}">
                                {{ ucfirst($agent->status) }}
                            </span>
                            <span class="text-gray-500">{{ $agent->category->name ?? 'No Category' }}</span>
                            <span class="text-gray-500">{{ $agent->currentZone->name ?? 'No Zone' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Agent Details -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Main Info -->
                    <div class="lg:col-span-2 space-y-6">
                        @if($agent->description)
                            <div class="bg-gray-50 rounded-lg p-6">
                                <h2 class="text-lg font-semibold text-gray-900 mb-3">Description</h2>
                                <p class="text-gray-700">{{ $agent->description }}</p>
                            </div>
                        @endif

                        @if($agent->skills)
                            <div class="bg-gray-50 rounded-lg p-6">
                                <h2 class="text-lg font-semibold text-gray-900 mb-3">Skills</h2>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($agent->skills as $skill)
                                        <span class="px-4 py-2 bg-white rounded-lg text-sm text-gray-700 shadow-sm">{{ $skill }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Tasks -->
                        <div class="bg-gray-50 rounded-lg p-6">
                            <h2 class="text-lg font-semibold text-gray-900 mb-3">Tasks ({{ $agent->tasks->count() }})</h2>
                            @if($agent->tasks->count() > 0)
                                <div class="space-y-3">
                                    @foreach($agent->tasks as $task)
                                        <div class="bg-white rounded-lg p-4 shadow-sm">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <h3 class="font-medium text-gray-900">{{ $task->title }}</h3>
                                                    @if($task->description)
                                                        <p class="text-sm text-gray-600 mt-1">{{ Str::limit($task->description, 100) }}</p>
                                                    @endif
                                                </div>
                                                <span class="px-3 py-1 text-sm rounded-full
                                                    {{ $task->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                                                    {{ $task->status === 'in_progress' ? 'bg-blue-100 text-blue-800' : '' }}
                                                    {{ $task->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}">
                                                    {{ ucfirst($task->status) }}
                                                </span>
                                            </div>
                                            <div class="flex items-center space-x-4 mt-3 text-sm text-gray-500">
                                                <span>Priority: {{ ucfirst($task->priority) }}</span>
                                                @if($task->due_date)
                                                    <span>Due: {{ $task->due_date->format('M d, Y') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-gray-500">No tasks assigned</p>
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
                                    <span class="text-gray-600">Total Tasks</span>
                                    <span class="font-semibold text-gray-900">{{ $agent->tasks->count() }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600">Completed</span>
                                    <span class="font-semibold text-green-600">{{ $agent->tasks->where('status', 'completed')->count() }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600">In Progress</span>
                                    <span class="font-semibold text-blue-600">{{ $agent->tasks->where('status', 'in_progress')->count() }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600">Pending</span>
                                    <span class="font-semibold text-yellow-600">{{ $agent->tasks->where('status', 'pending')->count() }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Position -->
                        <div class="bg-gray-50 rounded-lg p-6">
                            <h2 class="text-lg font-semibold text-gray-900 mb-3">Position</h2>
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600">X</span>
                                    <span class="font-semibold text-gray-900">{{ $agent->position_x ?? 'N/A' }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600">Y</span>
                                    <span class="font-semibold text-gray-900">{{ $agent->position_y ?? 'N/A' }}</span>
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
                                <button onclick="document.getElementById('status-modal').classList.remove('hidden')"
                                    class="w-full px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                                    Update Status
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Status Modal -->
<div id="status-modal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Update Agent Status</h2>
        <form action="{{ route('api.virtual-office.agents.update-status', $agent) }}" method="POST">
            @csrf
            @method('PATCH')
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="active" {{ $agent->status === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="busy" {{ $agent->status === 'busy' ? 'selected' : '' }}>Busy</option>
                    <option value="idle" {{ $agent->status === 'idle' ? 'selected' : '' }}>Idle</option>
                    <option value="offline" {{ $agent->status === 'offline' ? 'selected' : '' }}>Offline</option>
                </select>
            </div>
            <div class="flex items-center justify-end space-x-3">
                <button type="button" onclick="document.getElementById('status-modal').classList.add('hidden')"
                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                    Cancel
                </button>
                <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    Update
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
