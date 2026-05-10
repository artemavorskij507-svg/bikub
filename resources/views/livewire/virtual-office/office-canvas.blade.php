<div class="virtual-office-canvas">
    <!-- Header -->
    <div class="office-header bg-white shadow-sm border-b border-gray-200 px-6 py-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Virtual Office</h1>
                <p class="text-sm text-gray-500">2D Office with Pixel Agents</p>
            </div>
            <div class="flex items-center space-x-4">
                <!-- Search -->
                <div class="relative">
                    <input type="text" wire:model.live.debounce.300ms="searchQuery" placeholder="Search agents..."
                        class="w-64 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <svg class="absolute right-3 top-2.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <!-- Filters -->
                <select wire:change="filterByCategory($event.target.value)" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">All Categories</option>
                    @foreach(\App\Models\VirtualOffice\Category::all() as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
                <select wire:change="filterByStatus($event.target.value)" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    <option value="">All Statuses</option>
                    <option value="active">Active</option>
                    <option value="busy">Busy</option>
                    <option value="idle">Idle</option>
                    <option value="offline">Offline</option>
                </select>
                <button wire:click="clearFilters" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                    Clear Filters
                </button>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="office-content flex h-[calc(100vh-80px)]">
        <!-- Sidebar -->
        <div class="office-sidebar w-80 bg-white border-r border-gray-200 overflow-y-auto">
            <!-- Zones -->
            <div class="p-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900 mb-3">Office Zones</h2>
                <div class="space-y-2">
                    @foreach($zones as $zone)
                        <div wire:click="selectZone({{ $zone->id }})"
                            class="zone-card p-3 rounded-lg cursor-pointer transition {{ $selectedZone && $selectedZone->id === $zone->id ? 'bg-blue-50 border-2 border-blue-500' : 'bg-gray-50 hover:bg-gray-100' }}">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-3 h-3 rounded-full" style="background-color: {{ $zone->color ?? '#6B7280' }}"></div>
                                    <span class="font-medium text-gray-900">{{ $zone->name }}</span>
                                </div>
                                <span class="text-sm text-gray-500">{{ $zone->agents_count }} agents</span>
                            </div>
                            @if($zone->description)
                                <p class="text-xs text-gray-500 mt-1">{{ Str::limit($zone->description, 50) }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Agents List -->
            <div class="p-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900 mb-3">Agents ({{ $agents->count() }})</h2>
                <div class="space-y-2 max-h-64 overflow-y-auto">
                    @foreach($agents as $agent)
                        <div wire:click="selectAgent({{ $agent->id }})"
                            class="agent-card p-3 rounded-lg cursor-pointer transition {{ $selectedAgent && $selectedAgent->id === $agent->id ? 'bg-blue-50 border-2 border-blue-500' : 'bg-gray-50 hover:bg-gray-100' }}">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold"
                                    style="background-color: {{ $agent->category->color ?? '#6B7280' }}">
                                    {{ substr($agent->name, 0, 2) }}
                                </div>
                                <div class="flex-1">
                                    <div class="font-medium text-gray-900">{{ $agent->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $agent->role }}</div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="w-2 h-2 rounded-full {{ $agent->status === 'active' ? 'bg-green-500' : ($agent->status === 'busy' ? 'bg-yellow-500' : 'bg-gray-400') }}"></span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Recent Tasks -->
            <div class="p-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900 mb-3">Recent Tasks</h2>
                <div class="space-y-2 max-h-48 overflow-y-auto">
                    @foreach($tasks as $task)
                        <div class="task-card p-3 rounded-lg bg-gray-50">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="font-medium text-gray-900 text-sm">{{ $task->title }}</div>
                                    <div class="text-xs text-gray-500">{{ $task->agent->name ?? 'Unassigned' }}</div>
                                </div>
                                <span class="px-2 py-1 text-xs rounded-full
                                    {{ $task->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $task->status === 'in_progress' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $task->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}">
                                    {{ ucfirst($task->status) }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Recent Messages -->
            <div class="p-4">
                <h2 class="text-lg font-semibold text-gray-900 mb-3">Recent Messages</h2>
                <div class="space-y-2 max-h-48 overflow-y-auto">
                    @foreach($messages as $message)
                        <div class="message-card p-3 rounded-lg bg-gray-50">
                            <div class="flex items-start space-x-2">
                                <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white text-xs font-bold">
                                    {{ substr($message->sender->name ?? 'U', 0, 2) }}
                                </div>
                                <div class="flex-1">
                                    <div class="text-xs text-gray-500">{{ $message->sender->name ?? 'Unknown' }}</div>
                                    <div class="text-sm text-gray-900">{{ Str::limit($message->content, 50) }}</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Canvas Area -->
        <div class="office-canvas flex-1 bg-gray-100 relative overflow-hidden">
            <!-- Canvas -->
            <div id="office-canvas" class="w-full h-full relative" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <!-- Grid -->
                <div class="absolute inset-0 opacity-10">
                    <svg width="100%" height="100%">
                        <defs>
                            <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                                <path d="M 40 0 L 0 0 0 40" fill="none" stroke="white" stroke-width="1"/>
                            </pattern>
                        </defs>
                        <rect width="100%" height="100%" fill="url(#grid)" />
                    </svg>
                </div>

                <!-- Zones on Canvas -->
                @foreach($zones as $zone)
                    <div class="zone absolute rounded-lg border-2 border-white/30 backdrop-blur-sm"
                        style="left: {{ $zone->x ?? 50 }}px; top: {{ $zone->y ?? 50 }}px; width: {{ $zone->width ?? 200 }}px; height: {{ $zone->height ?? 150 }}px; background-color: {{ $zone->color ?? '#3B82F6' }}20;">
                        <div class="zone-label absolute top-2 left-2 px-2 py-1 bg-white/80 rounded text-xs font-medium text-gray-900">
                            {{ $zone->name }}
                        </div>
                        <!-- Agents in Zone -->
                        @foreach($agents->where('current_zone_id', $zone->id) as $agent)
                            <div class="agent absolute cursor-pointer transform hover:scale-110 transition"
                                style="left: {{ $agent->position_x ?? rand(10, 180) }}px; top: {{ $agent->position_y ?? rand(30, 120) }}px;"
                                wire:click="selectAgent({{ $agent->id }})">
                                <div class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold shadow-lg border-2 border-white"
                                    style="background-color: {{ $agent->category->color ?? '#6B7280' }}">
                                    {{ substr($agent->name, 0, 2) }}
                                </div>
                                <div class="absolute -bottom-5 left-1/2 transform -translate-x-1/2 whitespace-nowrap">
                                    <span class="px-2 py-1 bg-white/90 rounded text-xs font-medium text-gray-900 shadow">
                                        {{ $agent->name }}
                                    </span>
                                </div>
                                <!-- Status Indicator -->
                                <div class="absolute -top-1 -right-1 w-3 h-3 rounded-full border-2 border-white
                                    {{ $agent->status === 'active' ? 'bg-green-500' : '' }}
                                    {{ $agent->status === 'busy' ? 'bg-yellow-500' : '' }}
                                    {{ $agent->status === 'idle' ? 'bg-blue-500' : '' }}
                                    {{ $agent->status === 'offline' ? 'bg-gray-400' : '' }}">
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach

                <!-- Floating Agents (not in any zone) -->
                @foreach($agents->whereNull('current_zone_id') as $agent)
                    <div class="agent absolute cursor-pointer transform hover:scale-110 transition"
                        style="left: {{ $agent->position_x ?? rand(100, 700) }}px; top: {{ $agent->position_y ?? rand(100, 500) }}px;"
                        wire:click="selectAgent({{ $agent->id }})">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold shadow-lg border-2 border-white"
                            style="background-color: {{ $agent->category->color ?? '#6B7280' }}">
                            {{ substr($agent->name, 0, 2) }}
                        </div>
                        <div class="absolute -bottom-5 left-1/2 transform -translate-x-1/2 whitespace-nowrap">
                            <span class="px-2 py-1 bg-white/90 rounded text-xs font-medium text-gray-900 shadow">
                                {{ $agent->name }}
                            </span>
                        </div>
                        <!-- Status Indicator -->
                        <div class="absolute -top-1 -right-1 w-3 h-3 rounded-full border-2 border-white
                            {{ $agent->status === 'active' ? 'bg-green-500' : '' }}
                            {{ $agent->status === 'busy' ? 'bg-yellow-500' : '' }}
                            {{ $agent->status === 'idle' ? 'bg-blue-500' : '' }}
                            {{ $agent->status === 'offline' ? 'bg-gray-400' : '' }}">
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Canvas Controls -->
            <div class="absolute bottom-4 right-4 flex items-center space-x-2">
                <button wire:click="$set('showTaskModal', true)"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition shadow-lg">
                    + New Task
                </button>
                <button wire:click="$set('showMessageModal', true)"
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition shadow-lg">
                    + New Message
                </button>
            </div>
        </div>
    </div>

    <!-- Agent Modal -->
    @if($showAgentModal && $selectedAgent)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" wire:click.self="$set('showAgentModal', false)">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto" wire:click.stop>
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold text-gray-900">Agent Details</h2>
                        <button wire:click="$set('showAgentModal', false)" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="flex items-start space-x-6">
                        <div class="w-24 h-24 rounded-full flex items-center justify-center text-white text-3xl font-bold"
                            style="background-color: {{ $selectedAgent->category->color ?? '#6B7280' }}">
                            {{ substr($selectedAgent->name, 0, 2) }}
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-gray-900">{{ $selectedAgent->name }}</h3>
                            <p class="text-gray-600">{{ $selectedAgent->role }}</p>
                            <div class="flex items-center space-x-4 mt-2">
                                <span class="px-3 py-1 rounded-full text-sm font-medium
                                    {{ $selectedAgent->status === 'active' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $selectedAgent->status === 'busy' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ $selectedAgent->status === 'idle' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $selectedAgent->status === 'offline' ? 'bg-gray-100 text-gray-800' : '' }}">
                                    {{ ucfirst($selectedAgent->status) }}
                                </span>
                                <span class="text-sm text-gray-500">{{ $selectedAgent->category->name ?? 'No Category' }}</span>
                            </div>
                        </div>
                    </div>

                    @if($selectedAgent->description)
                        <div class="mt-6">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Description</h4>
                            <p class="text-gray-600">{{ $selectedAgent->description }}</p>
                        </div>
                    @endif

                    @if($selectedAgent->skills)
                        <div class="mt-6">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Skills</h4>
                            <div class="flex flex-wrap gap-2">
                                @foreach($selectedAgent->skills as $skill)
                                    <span class="px-3 py-1 bg-gray-100 rounded-full text-sm text-gray-700">{{ $skill }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="mt-6">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Current Zone</h4>
                        <p class="text-gray-600">{{ $selectedAgent->currentZone->name ?? 'Not assigned' }}</p>
                    </div>

                    <div class="mt-6">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Tasks ({{ $selectedAgent->tasks->count() }})</h4>
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            @foreach($selectedAgent->tasks as $task)
                                <div class="p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center justify-between">
                                        <span class="font-medium text-gray-900">{{ $task->title }}</span>
                                        <span class="px-2 py-1 text-xs rounded-full
                                            {{ $task->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                                            {{ $task->status === 'in_progress' ? 'bg-blue-100 text-blue-800' : '' }}
                                            {{ $task->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}">
                                            {{ ucfirst($task->status) }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-6 flex items-center space-x-3">
                        <label class="text-sm font-medium text-gray-700">Update Status:</label>
                        <select wire:change="updateAgentStatus({{ $selectedAgent->id }}, $event.target.value)"
                            class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="active" {{ $selectedAgent->status === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="busy" {{ $selectedAgent->status === 'busy' ? 'selected' : '' }}>Busy</option>
                            <option value="idle" {{ $selectedAgent->status === 'idle' ? 'selected' : '' }}>Idle</option>
                            <option value="offline" {{ $selectedAgent->status === 'offline' ? 'selected' : '' }}>Offline</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Task Modal -->
    @if($showTaskModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" wire:click.self="$set('showTaskModal', false)">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg" wire:click.stop>
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold text-gray-900">Create New Task</h2>
                        <button wire:click="$set('showTaskModal', false)" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <form wire:submit.prevent="createTask($event.target.elements)">
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
                                    @foreach($agents as $agent)
                                        <option value="{{ $agent->id }}">{{ $agent->name }} ({{ $agent->role }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Zone</label>
                                <select name="zone_id"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="">Select Zone</option>
                                    @foreach($zones as $zone)
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
                            <button type="button" wire:click="$set('showTaskModal', false)"
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
        </div>
    @endif

    <!-- Message Modal -->
    @if($showMessageModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" wire:click.self="$set('showMessageModal', false)">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg" wire:click.stop>
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold text-gray-900">Send Message</h2>
                        <button wire:click="$set('showMessageModal', false)" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <form wire:submit.prevent="sendMessage($event.target.elements)">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">From *</label>
                                <select name="sender_id" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="">Select Sender</option>
                                    @foreach($agents as $agent)
                                        <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">To (optional)</label>
                                <select name="receiver_id"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="">Select Receiver</option>
                                    @foreach($agents as $agent)
                                        <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Zone (optional)</label>
                                <select name="zone_id"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="">Select Zone</option>
                                    @foreach($zones as $zone)
                                        <option value="{{ $zone->id }}">{{ $zone->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Message *</label>
                                <textarea name="content" rows="4" required
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                                <select name="type"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="text">Text</option>
                                    <option value="task">Task</option>
                                    <option value="alert">Alert</option>
                                    <option value="notification">Notification</option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-6 flex items-center justify-end space-x-3">
                            <button type="button" wire:click="$set('showMessageModal', false)"
                                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                                Cancel
                            </button>
                            <button type="submit"
                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                                Send Message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
