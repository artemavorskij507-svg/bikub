<x-filament::page>
    @php
        $canvasUrl = \Illuminate\Support\Facades\Route::has('virtual-office.canvas')
            ? route('virtual-office.canvas')
            : url('/admin/virtual-2d-office');

        $agentsUrl = \Illuminate\Support\Facades\Route::has('filament.admin.resources.virtual-office.agents.index')
            ? route('filament.admin.resources.virtual-office.agents.index')
            : (\Illuminate\Support\Facades\Route::has('filament.admin.resources.agents.index')
                ? route('filament.admin.resources.agents.index')
                : url('/admin/virtual-2d-office'));

        $tasksUrl = \Illuminate\Support\Facades\Route::has('virtual-office.tasks')
            ? route('virtual-office.tasks')
            : url('/admin/virtual-2d-office');

        $zonesUrl = \Illuminate\Support\Facades\Route::has('virtual-office.zones')
            ? route('virtual-office.zones')
            : url('/admin/virtual-2d-office');

        $recentMessages = collect();
        $activeAgentsCount = 0;
        $busyAgentsCount = 0;
        $idleAgentsCount = 0;
        $offlineAgentsCount = 0;
        $pendingTasksCount = 0;
        $inProgressTasksCount = 0;
        $completedTasksCount = 0;
        $cancelledTasksCount = 0;

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('messages')) {
                $recentMessages = \App\Models\VirtualOffice\Message::with(['sender'])->latest()->take(5)->get();
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('VirtualOfficeDashboard: failed to load recent messages', [
                'error' => $e->getMessage(),
            ]);
        }

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('agents')) {
                $activeAgentsCount = \App\Models\VirtualOffice\Agent::where('status', 'active')->count();
                $busyAgentsCount = \App\Models\VirtualOffice\Agent::where('status', 'busy')->count();
                $idleAgentsCount = \App\Models\VirtualOffice\Agent::where('status', 'idle')->count();
                $offlineAgentsCount = \App\Models\VirtualOffice\Agent::where('status', 'offline')->count();
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('VirtualOfficeDashboard: failed to load agent counters', [
                'error' => $e->getMessage(),
            ]);
        }

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('tasks')) {
                $pendingTasksCount = \App\Models\VirtualOffice\Task::where('status', 'pending')->count();
                $inProgressTasksCount = \App\Models\VirtualOffice\Task::where('status', 'in_progress')->count();
                $completedTasksCount = \App\Models\VirtualOffice\Task::where('status', 'completed')->count();
                $cancelledTasksCount = \App\Models\VirtualOffice\Task::where('status', 'cancelled')->count();
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('VirtualOfficeDashboard: failed to load task counters', [
                'error' => $e->getMessage(),
            ]);
        }
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Quick Actions -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h2>
            <div class="grid grid-cols-2 gap-4">
                <a href="{{ $canvasUrl }}" 
                    class="flex items-center justify-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition">
                    <div class="text-center">
                        <div class="w-12 h-12 mx-auto mb-2 bg-blue-500 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-gray-900">View Canvas</span>
                    </div>
                </a>
                <a href="{{ $agentsUrl }}" 
                    class="flex items-center justify-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition">
                    <div class="text-center">
                        <div class="w-12 h-12 mx-auto mb-2 bg-green-500 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-gray-900">Manage Agents</span>
                    </div>
                </a>
                <a href="{{ $tasksUrl }}" 
                    class="flex items-center justify-center p-4 bg-yellow-50 rounded-lg hover:bg-yellow-100 transition">
                    <div class="text-center">
                        <div class="w-12 h-12 mx-auto mb-2 bg-yellow-500 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-gray-900">View Tasks</span>
                    </div>
                </a>
                <a href="{{ $zonesUrl }}" 
                    class="flex items-center justify-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition">
                    <div class="text-center">
                        <div class="w-12 h-12 mx-auto mb-2 bg-purple-500 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-gray-900">View Zones</span>
                    </div>
                </a>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Recent Activity</h2>
            <div class="space-y-4">
                @forelse($recentMessages as $message)
                    <div class="flex items-start space-x-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold"
                            style="background-color: {{ $message->sender->category->color ?? '#6B7280' }}">
                            {{ substr($message->sender->name ?? 'U', 0, 2) }}
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-gray-900">{{ $message->sender->name ?? 'Unknown' }}</div>
                            <div class="text-sm text-gray-600">{{ Str::limit($message->content, 50) }}</div>
                            <div class="text-xs text-gray-400">{{ $message->created_at->diffForHumans() }}</div>
                        </div>
                    </div>
                @empty
                    <div class="text-sm text-gray-500">No recent activity available.</div>
                @endforelse
            </div>
        </div>

        <!-- Agent Status Overview -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Agent Status Overview</h2>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <div class="w-3 h-3 rounded-full bg-green-500"></div>
                        <span class="text-sm text-gray-600">Active</span>
                    </div>
                    <span class="text-sm font-semibold text-gray-900">{{ $activeAgentsCount }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                        <span class="text-sm text-gray-600">Busy</span>
                    </div>
                    <span class="text-sm font-semibold text-gray-900">{{ $busyAgentsCount }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                        <span class="text-sm text-gray-600">Idle</span>
                    </div>
                    <span class="text-sm font-semibold text-gray-900">{{ $idleAgentsCount }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <div class="w-3 h-3 rounded-full bg-gray-400"></div>
                        <span class="text-sm text-gray-600">Offline</span>
                    </div>
                    <span class="text-sm font-semibold text-gray-900">{{ $offlineAgentsCount }}</span>
                </div>
            </div>
        </div>

        <!-- Task Status Overview -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Task Status Overview</h2>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                        <span class="text-sm text-gray-600">Pending</span>
                    </div>
                    <span class="text-sm font-semibold text-gray-900">{{ $pendingTasksCount }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                        <span class="text-sm text-gray-600">In Progress</span>
                    </div>
                    <span class="text-sm font-semibold text-gray-900">{{ $inProgressTasksCount }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <div class="w-3 h-3 rounded-full bg-green-500"></div>
                        <span class="text-sm text-gray-600">Completed</span>
                    </div>
                    <span class="text-sm font-semibold text-gray-900">{{ $completedTasksCount }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <div class="w-3 h-3 rounded-full bg-red-500"></div>
                        <span class="text-sm text-gray-600">Cancelled</span>
                    </div>
                    <span class="text-sm font-semibold text-gray-900">{{ $cancelledTasksCount }}</span>
                </div>
            </div>
        </div>
    </div>
</x-filament::page>
