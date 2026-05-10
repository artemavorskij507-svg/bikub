@extends('virtual-office.index')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <!-- Back Button -->
                <div class="mb-6">
                    <a href="{{ route('virtual-office.tasks') }}" class="text-blue-600 hover:text-blue-700 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Back to Tasks
                    </a>
                </div>

                <!-- Task Header -->
                <div class="flex items-start justify-between mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">{{ $task->title }}</h1>
                        <div class="flex items-center space-x-4 mt-3">
                            <span class="px-4 py-2 rounded-full text-sm font-medium
                                {{ $task->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $task->status === 'in_progress' ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $task->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $task->status === 'cancelled' ? 'bg-red-100 text-red-800' : '' }}">
                                {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                            </span>
                            <span class="px-4 py-2 rounded-full text-sm font-medium
                                {{ $task->priority === 'urgent' ? 'bg-red-100 text-red-800' : '' }}
                                {{ $task->priority === 'high' ? 'bg-orange-100 text-orange-800' : '' }}
                                {{ $task->priority === 'medium' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $task->priority === 'low' ? 'bg-green-100 text-green-800' : '' }}">
                                {{ ucfirst($task->priority) }} Priority
                            </span>
                        </div>
                    </div>
                    <button onclick="document.getElementById('status-modal').classList.remove('hidden')"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        Update Status
                    </button>
                </div>

                <!-- Task Details -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Main Info -->
                    <div class="lg:col-span-2 space-y-6">
                        @if($task->description)
                            <div class="bg-gray-50 rounded-lg p-6">
                                <h2 class="text-lg font-semibold text-gray-900 mb-3">Description</h2>
                                <p class="text-gray-700">{{ $task->description }}</p>
                            </div>
                        @endif

                        <!-- Agent Info -->
                        @if($task->agent)
                            <div class="bg-gray-50 rounded-lg p-6">
                                <h2 class="text-lg font-semibold text-gray-900 mb-3">Assigned Agent</h2>
                                <div class="flex items-center space-x-4">
                                    <div class="w-16 h-16 rounded-full flex items-center justify-center text-white text-2xl font-bold"
                                        style="background-color: {{ $task->agent->category->color ?? '#6B7280' }}">
                                        {{ substr($task->agent->name, 0, 2) }}
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-900">{{ $task->agent->name }}</h3>
                                        <p class="text-gray-600">{{ $task->agent->role }}</p>
                                        <span class="px-2 py-1 text-xs rounded-full
                                            {{ $task->agent->status === 'active' ? 'bg-green-100 text-green-800' : '' }}
                                            {{ $task->agent->status === 'busy' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                            {{ $task->agent->status === 'idle' ? 'bg-blue-100 text-blue-800' : '' }}
                                            {{ $task->agent->status === 'offline' ? 'bg-gray-100 text-gray-800' : '' }}">
                                            {{ ucfirst($task->agent->status) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <a href="{{ route('virtual-office.agents.show', $task->agent) }}"
                                        class="text-blue-600 hover:text-blue-700">View Agent Details →</a>
                                </div>
                            </div>
                        @endif

                        <!-- Zone Info -->
                        @if($task->zone)
                            <div class="bg-gray-50 rounded-lg p-6">
                                <h2 class="text-lg font-semibold text-gray-900 mb-3">Zone</h2>
                                <div class="flex items-center space-x-4">
                                    <div class="w-12 h-12 rounded-lg flex items-center justify-center"
                                        style="background-color: {{ $task->zone->color ?? '#3B82F6' }}20">
                                        <div class="w-6 h-6 rounded-full" style="background-color: {{ $task->zone->color ?? '#3B82F6' }}"></div>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-900">{{ $task->zone->name }}</h3>
                                        @if($task->zone->description)
                                            <p class="text-gray-600">{{ Str::limit($task->zone->description, 100) }}</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <a href="{{ route('virtual-office.zones.show', $task->zone) }}"
                                        class="text-blue-600 hover:text-blue-700">View Zone Details →</a>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Quick Info -->
                        <div class="bg-gray-50 rounded-lg p-6">
                            <h2 class="text-lg font-semibold text-gray-900 mb-3">Quick Info</h2>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600">Created</span>
                                    <span class="font-semibold text-gray-900">{{ $task->created_at->format('M d, Y') }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600">Updated</span>
                                    <span class="font-semibold text-gray-900">{{ $task->updated_at->format('M d, Y') }}</span>
                                </div>
                                @if($task->due_date)
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-600">Due Date</span>
                                        <span class="font-semibold text-gray-900">{{ $task->due_date->format('M d, Y') }}</span>
                                    </div>
                                @endif
                                @if($task->completed_at)
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-600">Completed</span>
                                        <span class="font-semibold text-green-600">{{ $task->completed_at->format('M d, Y') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="bg-gray-50 rounded-lg p-6">
                            <h2 class="text-lg font-semibold text-gray-900 mb-3">Actions</h2>
                            <div class="space-y-3">
                                <button onclick="document.getElementById('status-modal').classList.remove('hidden')"
                                    class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                    Update Status
                                </button>
                                @if($task->agent)
                                    <a href="{{ route('virtual-office.agents.show', $task->agent) }}"
                                        class="block w-full text-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                                        View Agent
                                    </a>
                                @endif
                                @if($task->zone)
                                    <a href="{{ route('virtual-office.zones.show', $task->zone) }}"
                                        class="block w-full text-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                                        View Zone
                                    </a>
                                @endif
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
        <h2 class="text-xl font-bold text-gray-900 mb-4">Update Task Status</h2>
        <form action="{{ route('api.virtual-office.tasks.update-status', $task) }}" method="POST">
            @csrf
            @method('PATCH')
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="status"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="pending" {{ $task->status === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="in_progress" {{ $task->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="completed" {{ $task->status === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="cancelled" {{ $task->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
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
