@extends('virtual-office.index')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Tasks</h1>
                        <p class="text-sm text-gray-500">Manage all virtual office tasks</p>
                    </div>
                    <button onclick="document.getElementById('create-task-modal').classList.remove('hidden')"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        + New Task
                    </button>
                </div>

                <!-- Filters -->
                <div class="mb-6 flex items-center space-x-4">
                    <input type="text" id="search" placeholder="Search tasks..."
                        class="w-64 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <select id="status-filter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <select id="priority-filter" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">All Priorities</option>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>

                <!-- Tasks Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Task</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Agent</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Zone</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach(\App\Models\VirtualOffice\Task::with(['agent', 'zone'])->latest()->get() as $task)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $task->title }}</div>
                                        @if($task->description)
                                            <div class="text-sm text-gray-500">{{ Str::limit($task->description, 50) }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($task->agent)
                                            <div class="flex items-center">
                                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold mr-2"
                                                    style="background-color: {{ $task->agent->category->color ?? '#6B7280' }}">
                                                    {{ substr($task->agent->name, 0, 2) }}
                                                </div>
                                                <div class="text-sm text-gray-900">{{ $task->agent->name }}</div>
                                            </div>
                                        @else
                                            <span class="text-sm text-gray-500">Unassigned</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm text-gray-900">{{ $task->zone->name ?? 'No Zone' }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-3 py-1 text-sm rounded-full
                                            {{ $task->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                                            {{ $task->status === 'in_progress' ? 'bg-blue-100 text-blue-800' : '' }}
                                            {{ $task->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                            {{ $task->status === 'cancelled' ? 'bg-red-100 text-red-800' : '' }}">
                                            {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-3 py-1 text-sm rounded-full
                                            {{ $task->priority === 'urgent' ? 'bg-red-100 text-red-800' : '' }}
                                            {{ $task->priority === 'high' ? 'bg-orange-100 text-orange-800' : '' }}
                                            {{ $task->priority === 'medium' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                            {{ $task->priority === 'low' ? 'bg-green-100 text-green-800' : '' }}">
                                            {{ ucfirst($task->priority) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $task->due_date ? $task->due_date->format('M d, Y') : 'No due date' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="{{ route('virtual-office.tasks.show', $task) }}" class="text-blue-600 hover:text-blue-900 mr-3">View</a>
                                        <button onclick="openStatusModal({{ $task->id }}, '{{ $task->status }}')"
                                            class="text-gray-600 hover:text-gray-900">Update</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Task Modal -->
<div id="create-task-modal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Create New Task</h2>
        <form action="{{ route('api.virtual-office.tasks.store') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                    <input type="text" name="title" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="3"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Agent *</label>
                    <select name="agent_id" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Agent</option>
                        @foreach(\App\Models\VirtualOffice\Agent::all() as $agent)
                            <option value="{{ $agent->id }}">{{ $agent->name }} ({{ $agent->role }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Zone</label>
                    <select name="zone_id"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">Select Zone</option>
                        @foreach(\App\Models\VirtualOffice\OfficeZone::all() as $zone)
                            <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                    <select name="priority"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                    <input type="date" name="due_date"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <div class="mt-6 flex items-center justify-end space-x-3">
                <button type="button" onclick="document.getElementById('create-task-modal').classList.add('hidden')"
                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                    Cancel
                </button>
                <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    Create Task
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Update Status Modal -->
<div id="status-modal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Update Task Status</h2>
        <form id="status-form" method="POST">
            @csrf
            @method('PATCH')
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="status" id="status-select"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
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

<script>
function openStatusModal(taskId, currentStatus) {
    document.getElementById('status-form').action = `/api/virtual-office/tasks/${taskId}/status`;
    document.getElementById('status-select').value = currentStatus;
    document.getElementById('status-modal').classList.remove('hidden');
}
</script>
@endsection
