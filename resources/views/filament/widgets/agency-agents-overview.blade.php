<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            🏢 Agency Agents - 2D Office Overview
        </x-slot>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-4">
            <!-- Total Agents -->
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg p-3 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs opacity-80">Agents</p>
                        <p class="text-2xl font-bold">{{ $systemOverview['agents']['total'] ?? 0 }}</p>
                    </div>
                    <div class="text-3xl opacity-80">🤖</div>
                </div>
            </div>

            <!-- Active Agents -->
            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg p-3 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs opacity-80">Active</p>
                        <p class="text-2xl font-bold">{{ $systemOverview['agents']['active'] ?? 0 }}</p>
                    </div>
                    <div class="text-3xl opacity-80">✅</div>
                </div>
            </div>

            <!-- Busy Agents -->
            <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-lg p-3 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs opacity-80">Busy</p>
                        <p class="text-2xl font-bold">{{ $systemOverview['agents']['busy'] ?? 0 }}</p>
                    </div>
                    <div class="text-3xl opacity-80">⏳</div>
                </div>
            </div>

            <!-- Tasks Completed -->
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg p-3 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs opacity-80">Tasks</p>
                        <p class="text-2xl font-bold">{{ $systemOverview['tasks']['completed'] ?? 0 }}</p>
                    </div>
                    <div class="text-3xl opacity-80">📋</div>
                </div>
            </div>

            <!-- Avg Performance -->
            <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-lg p-3 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs opacity-80">Perf</p>
                        <p class="text-2xl font-bold">{{ $systemOverview['performance']['average_score'] ?? 0 }}%</p>
                    </div>
                    <div class="text-3xl opacity-80">📈</div>
                </div>
            </div>

            <!-- Messages -->
            <div class="bg-gradient-to-br from-pink-500 to-pink-600 rounded-lg p-3 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs opacity-80">Messages</p>
                        <p class="text-2xl font-bold">{{ $systemOverview['communications']['total_messages'] ?? 0 }}</p>
                    </div>
                    <div class="text-3xl opacity-80">💬</div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <!-- Zone Statistics -->
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">
                    🏢 Office Zones
                </h3>
                <div class="space-y-2">
                    @foreach($zoneStats as $zone)
                        <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-800 rounded">
                            <div class="flex items-center">
                                <span class="text-lg mr-2">{{ $zone['icon'] }}</span>
                                <span class="text-xs text-gray-700 dark:text-gray-300">
                                    {{ $zone['display_name'] }}
                                </span>
                            </div>
                            <div class="flex items-center">
                                <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 mr-2">
                                    <div
                                        class="bg-blue-600 h-1.5 rounded-full"
                                        style="width: {{ $zone['percentage'] }}%"
                                    ></div>
                                </div>
                                <span class="text-xs font-medium text-gray-900 dark:text-white">
                                    {{ $zone['occupancy'] }}/{{ $zone['capacity'] }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Top Performers -->
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">
                    🏆 Top Performers
                </h3>
                <div class="space-y-2">
                    @foreach($topPerformers as $index => $performer)
                        <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-800 rounded">
                            <div class="flex items-center">
                                <span class="text-xs font-bold text-gray-400 dark:text-gray-500 w-4">
                                    {{ $index + 1 }}
                                </span>
                                <div
                                    class="w-6 h-6 rounded flex items-center justify-center text-xs font-bold ml-2"
                                    style="background-color: {{ $performer['color'] }};"
                                >
                                    {{ $performer['emoji'] }}
                                </div>
                                <div class="ml-2">
                                    <p class="text-xs font-medium text-gray-900 dark:text-white">
                                        {{ $performer['name'] }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $performer['category'] }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-xs font-bold text-gray-900 dark:text-white">
                                    {{ $performer['performance_score'] }}%
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Recent Activity -->
            <div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">
                    🕐 Recent Activity
                </h3>
                <div class="space-y-2 max-h-48 overflow-y-auto">
                    @foreach($recentActivity as $activity)
                        <div class="flex items-start p-2 bg-gray-50 dark:bg-gray-800 rounded">
                            <span class="text-sm mr-2">{{ $activity['agent_emoji'] }}</span>
                            <div class="flex-1">
                                <div class="text-xs font-medium text-gray-900 dark:text-white">
                                    {{ $activity['agent_name'] }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $activity['description'] }}
                                </div>
                                <div class="text-xs text-gray-400 dark:text-gray-500">
                                    {{ $activity['started_at'] }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('filament.admin.pages.virtual-2d-office') }}"
                   class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                    🏢 Open 2D Office
                </a>
                <button
                    onclick="location.reload()"
                    class="inline-flex items-center px-3 py-1.5 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-lg transition-colors">
                    🔄 Refresh
                </button>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
