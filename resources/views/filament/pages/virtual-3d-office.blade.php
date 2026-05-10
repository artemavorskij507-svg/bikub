<x-filament-panels::page>
    <div class="virtual-3d-office">
        <!-- Header -->
        <div class="office-header mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        🏢 Virtual 3D Office
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400">
                        Real-time visualization of AI agents collaboration
                    </p>
                </div>
                <div class="flex gap-2">
                    <x-filament::button
                        wire:click="refreshData"
                        icon="heroicon-o-arrow-path"
                        color="gray"
                    >
                        Refresh
                    </x-filament::button>
                    <x-filament::button
                        wire:click="toggleViewMode"
                        icon="{{ $viewMode === '3d' ? 'heroicon-o-list-bullet' : 'heroicon-o-cube' }}"
                        color="primary"
                    >
                        {{ $viewMode === '3d' ? 'List View' : '3D View' }}
                    </x-filament::button>
                </div>
            </div>
        </div>

        <!-- System Overview -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                        <span class="text-2xl">🤖</span>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Total Agents</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ $systemOverview['agents']['total'] ?? 0 }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                        <span class="text-2xl">✅</span>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Active</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ $systemOverview['agents']['active'] ?? 0 }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-2 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                        <span class="text-2xl">📋</span>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Tasks</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ $systemOverview['tasks']['completed'] ?? 0 }}/{{ $systemOverview['tasks']['total'] ?? 0 }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg">
                        <span class="text-2xl">📈</span>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-gray-600 dark:text-gray-400">Avg Performance</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ $systemOverview['performance']['average_score'] ?? 0 }}%
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-6">
            <div class="flex flex-wrap gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Category
                    </label>
                    <select
                        wire:change="filterByCategory($event.target.value)"
                        class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    >
                        <option value="all">All Categories</option>
                        @foreach($categories as $key => $category)
                            <option value="{{ $key }}" {{ $filterCategory === $key ? 'selected' : '' }}>
                                {{ $category['icon'] }} {{ $category['name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Status
                    </label>
                    <select
                        wire:change="filterByStatus($event.target.value)"
                        class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                    >
                        <option value="all">All Statuses</option>
                        <option value="active" {{ $filterStatus === 'active' ? 'selected' : '' }}>🟢 Active</option>
                        <option value="busy" {{ $filterStatus === 'busy' ? 'selected' : '' }}>🟡 Busy</option>
                        <option value="idle" {{ $filterStatus === 'idle' ? 'selected' : '' }}>⚪ Idle</option>
                        <option value="offline" {{ $filterStatus === 'offline' ? 'selected' : '' }}>⚫ Offline</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- 3D Office View -->
            <div class="lg:col-span-2">
                @if($viewMode === '3d')
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            🏢 3D Office Space
                        </h3>
                        <div
                            id="office-3d-container"
                            class="relative w-full h-[600px] bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-900 dark:to-gray-800 rounded-lg overflow-hidden"
                            style="perspective: 1000px;"
                        >
                            <!-- 3D Office Grid -->
                            <div class="absolute inset-0 grid grid-cols-6 grid-rows-6 gap-1 p-4">
                                @foreach($agents as $agent)
                                    @php
                                        $x = (($agent['position']['x'] + 10) / 20) * 100;
                                        $y = (($agent['position']['y'] + 5) / 10) * 100;
                                        $statusColor = match($agent['status']) {
                                            'active' => 'bg-green-500',
                                            'busy' => 'bg-yellow-500',
                                            'idle' => 'bg-gray-400',
                                            default => 'bg-gray-600'
                                        };
                                    @endphp
                                    <div
                                        class="absolute transform -translate-x-1/2 -translate-y-1/2 cursor-pointer transition-all duration-300 hover:scale-110 hover:z-10"
                                        style="left: {{ $x }}%; top: {{ $y }}%;"
                                        wire:click="selectAgent({{ $agent['id'] }})"
                                    >
                                        <div class="relative">
                                            <!-- Agent Avatar -->
                                            <div
                                                class="w-12 h-12 rounded-full flex items-center justify-center text-white text-xl font-bold shadow-lg border-2 border-white dark:border-gray-700"
                                                style="background-color: {{ $agent['color'] }};"
                                            >
                                                {{ $agent['emoji'] }}
                                            </div>
                                            <!-- Status Indicator -->
                                            <div class="absolute -bottom-1 -right-1 w-4 h-4 {{ $statusColor }} rounded-full border-2 border-white dark:border-gray-800"></div>
                                            <!-- Name Label -->
                                            <div class="absolute top-full left-1/2 transform -translate-x-1/2 mt-1 whitespace-nowrap">
                                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 px-2 py-0.5 rounded shadow">
                                                    {{ $agent['name'] }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Office Environment -->
                            <div class="absolute inset-0 pointer-events-none">
                                <!-- Floor Grid -->
                                <svg class="w-full h-full opacity-20">
                                    <defs>
                                        <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                                            <path d="M 40 0 L 0 0 0 40" fill="none" stroke="currentColor" stroke-width="1"/>
                                        </pattern>
                                    </defs>
                                    <rect width="100%" height="100%" fill="url(#grid)" class="text-gray-400 dark:text-gray-600"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                @else
                    <!-- List View -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                📋 Agents List
                            </h3>
                        </div>
                        <div class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($agents as $agent)
                                <div
                                    class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition-colors"
                                    wire:click="selectAgent({{ $agent['id'] }})"
                                >
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <div
                                                class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold"
                                                style="background-color: {{ $agent['color'] }};"
                                            >
                                                {{ $agent['emoji'] }}
                                            </div>
                                            <div class="ml-3">
                                                <p class="font-medium text-gray-900 dark:text-white">
                                                    {{ $agent['name'] }}
                                                </p>
                                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                                    {{ $agent['category'] }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-4">
                                            <div class="text-right">
                                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ $agent['performance_score'] }}%
                                                </p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $agent['tasks_completed'] }} tasks
                                                </p>
                                            </div>
                                            <div class="w-3 h-3 rounded-full {{ match($agent['status']) {
                                                'active' => 'bg-green-500',
                                                'busy' => 'bg-yellow-500',
                                                'idle' => 'bg-gray-400',
                                                default => 'bg-gray-600'
                                            } }}"></div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- Agent Details Panel -->
            <div class="lg:col-span-1">
                @if($selectedAgent)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <!-- Agent Header -->
                        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-center">
                                <div
                                    class="w-16 h-16 rounded-full flex items-center justify-center text-white text-2xl font-bold"
                                    style="background-color: {{ $selectedAgent->color }};"
                                >
                                    {{ $selectedAgent->emoji }}
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ $selectedAgent->name }}
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $selectedAgent->category }}
                                    </p>
                                    <div class="flex items-center mt-1">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                            {{ $selectedAgent->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                                            {{ $selectedAgent->status === 'busy' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : '' }}
                                            {{ $selectedAgent->status === 'idle' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' : '' }}
                                        ">
                                            {{ ucfirst($selectedAgent->status) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Agent Stats -->
                        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                📊 Performance
                            </h4>
                            <div class="space-y-3">
                                <div>
                                    <div class="flex justify-between text-sm mb-1">
                                        <span class="text-gray-600 dark:text-gray-400">Score</span>
                                        <span class="font-medium text-gray-900 dark:text-white">
                                            {{ $selectedAgent->performance_score }}%
                                        </span>
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div
                                            class="bg-blue-600 h-2 rounded-full"
                                            style="width: {{ $selectedAgent->performance_score }}%"
                                        ></div>
                                    </div>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Tasks Completed</span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $selectedAgent->tasks_completed }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Agent Description -->
                        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                ℹ️ About
                            </h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $selectedAgent->description }}
                            </p>
                        </div>

                        <!-- Recent Tasks -->
                        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                📋 Recent Tasks
                            </h4>
                            @if($selectedAgent->tasks->count() > 0)
                                <div class="space-y-2">
                                    @foreach($selectedAgent->tasks->take(5) as $task)
                                        <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded">
                                            <span class="text-sm text-gray-700 dark:text-gray-300 truncate">
                                                {{ $task->title }}
                                            </span>
                                            <span class="text-xs px-2 py-0.5 rounded
                                                {{ $task->status === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                                                {{ $task->status === 'in_progress' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : '' }}
                                                {{ $task->status === 'pending' ? 'bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-200' : '' }}
                                            ">
                                                {{ ucfirst($task->status) }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    No tasks yet
                                </p>
                            @endif
                        </div>

                        <!-- Actions -->
                        <div class="p-4">
                            <x-filament::button
                                wire:click="toggleCommunications"
                                class="w-full"
                                color="gray"
                            >
                                💬 View Communications
                            </x-filament::button>
                        </div>
                    </div>
                @else
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-8 text-center">
                        <div class="text-6xl mb-4">👆</div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                            Select an Agent
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Click on an agent in the 3D office to view their details
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Real-time updates
        setInterval(() => {
            @this.call('refreshData');
        }, 5000);

        // 3D Office interactions
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('office-3d-container');
            if (container) {
                // Add subtle animation
                container.addEventListener('mousemove', function(e) {
                    const rect = container.getBoundingClientRect();
                    const x = (e.clientX - rect.left) / rect.width;
                    const y = (e.clientY - rect.top) / rect.height;
                    
                    container.style.transform = `rotateY(${(x - 0.5) * 5}deg) rotateX(${(y - 0.5) * -5}deg)`;
                });

                container.addEventListener('mouseleave', function() {
                    container.style.transform = 'rotateY(0deg) rotateX(0deg)';
                });
            }
        });
    </script>
    @endpush
</x-filament-panels::page>
