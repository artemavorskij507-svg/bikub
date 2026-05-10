<x-filament::page>
    <style>
        /* Safe inline styles for Glassmorphism that don't rely on Tailwind JIT compilation */
        [x-cloak] { display: none !important; }
        .glass-panel {
            background-color: rgba(17, 24, 39, 0.7); /* gray-900 at 70% */
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1.5rem; /* 24px */
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.5);
            padding: 1.5rem;
        }
        .glass-card {
            background: linear-gradient(135deg, rgba(31, 41, 55, 0.9) 0%, rgba(17, 24, 39, 0.95) 100%);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 1rem;
            padding: 1rem;
        }
        .pill-badge {
            background-color: rgba(55, 65, 81, 0.5);
            border: 1px solid rgba(75, 85, 99, 0.4);
            border-radius: 9999px;
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 700;
            color: #9CA3AF;
        }
        .cyber-bg {
            background-color: #0B0E14;
            color: #E5E7EB;
        }
        .cyber-accent-text { color: #34D399; } /* emerald-400 */
        .cyber-border { border-color: #374151; } /* gray-700 */
        .chat-container {
            background-color: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 1rem;
        }
    </style>

    <div wire:poll.3000ms="refreshRunProgress" class="cyber-bg p-6 rounded-2xl border cyber-border w-full flex flex-col gap-6" style="min-height: 85vh;">

        <!-- Header Panel -->
        <div class="glass-panel w-full">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <span class="text-xs font-bold tracking-widest text-gray-500 uppercase">Agent Workspace &bull; Glassmorphism Core</span>
                </div>
                <!-- Action Buttons: System Log & Sync -->
                <div class="flex items-center gap-2">
                    <button wire:click="toggleSystemMessages" class="pill-badge hover:bg-gray-700 transition" style="cursor: pointer;">
                        {{ $showSystemMessages ? 'Hide System Logs' : 'Show System Logs' }}
                    </button>
                    <button wire:click="syncAgencyAgents" class="pill-badge hover:bg-gray-700 transition flex items-center gap-1" style="cursor: pointer; color: #60A5FA;">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        Sync Agents
                    </button>
                </div>
            </div>

            <!-- Run Title & Status -->
            @if(empty($recentRuns))
                <h1 class="text-2xl font-bold text-white">No active tasks. Awaiting input...</h1>
            @else
                <h1 class="text-2xl font-bold text-white leading-tight mb-2">
                    {{ Str::limit(data_get($activeRunSummary, 'goal', 'No active goal'), 100) }}
                </h1>
                <div class="flex items-center gap-4 text-sm font-semibold">
                    <span class="text-gray-400">Run #{{ $activeRunId ?? 'N/A' }} | {{ strtolower($activeRunStatus ?? 'idle') }}</span>
                    <span class="cyber-accent-text flex items-center gap-1">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        Coordinator {{ $this->chiefAgent?->name ?? 'Director' }} online
                    </span>
                    <span class="text-gray-500">Total agents: {{ $this->activeAgentsOnlineCount }} / 103</span>
                </div>
            @endif

            <!-- Top Dashboard Stats -->
            <div class="mt-6 flex flex-wrap items-center gap-4">
                <div class="glass-card flex-1 min-w-[120px]">
                    <p class="text-xs font-bold text-gray-500 tracking-wider">PROGRESS</p>
                    <p class="text-2xl font-black text-white mt-1">{{ data_get($activeRunSummary, 'progress_percent', 0) }}%</p>
                </div>
                <div class="glass-card flex-1 min-w-[120px]">
                    <p class="text-xs font-bold text-gray-500 tracking-wider">RISK LEVEL</p>
                    <p class="text-xl font-bold {{ (data_get($activeRunSummary, 'risk_level') === 'high') ? 'text-red-400' : 'text-yellow-400' }} mt-1 capitalize">
                        {{ data_get($activeRunSummary, 'risk_level', 'low') }}
                    </p>
                </div>
                <div class="glass-card flex-1 min-w-[120px]">
                    <p class="text-xs font-bold text-gray-500 tracking-wider">ARTIFACTS</p>
                    <p class="text-xl font-bold text-gray-200 mt-1">{{ count($workspaceArtifacts) }}</p>
                </div>
                <div class="glass-card flex-1 min-w-[120px]">
                    <p class="text-xs font-bold text-gray-500 tracking-wider">EVENTS</p>
                    <p class="text-xl font-bold text-gray-200 mt-1">{{ count($workspaceEvents) }}</p>
                </div>
                <div class="glass-card flex-1 min-w-[120px]">
                    <p class="text-xs font-bold text-gray-500 tracking-wider">HORIZON STATUS</p>
                    <p class="text-sm font-bold text-gray-300 mt-1 uppercase">{{ data_get($health, 'horizon_status', 'unknown') }}</p>
                </div>
            </div>
        </div>

        <!-- Middle Section: Left (Threads & Team) + Right (Logs/Chat) -->
        <div class="flex flex-col lg:flex-row gap-6 h-full items-stretch">
            
            <!-- Left Sidebar -->
            <div class="w-full lg:w-1/3 flex flex-col gap-6">
                
                <!-- Run History -->
                <div class="glass-panel overflow-hidden flex flex-col h-64">
                    <h3 class="text-xs font-bold tracking-widest text-gray-500 mb-4 sticky top-0 bg-transparent">RECENT RUNS</h3>
                    <div class="overflow-y-auto pr-2 space-y-3" style="max-height: 100%;">
                        @forelse($recentRuns as $historyRun)
                            <div wire:click="selectRun({{ $historyRun['id'] }})" class="p-3 rounded-xl border {{ $activeRunId === $historyRun['id'] ? 'border-emerald-500 bg-emerald-900/20' : 'border-gray-700 hover:border-gray-500 bg-gray-800/50' }} transition cursor-pointer">
                                <div class="flex justify-between items-start mb-1 text-xs">
                                    <span class="font-bold text-gray-300">Run #{{ $historyRun['id'] }}</span>
                                    <span class="font-mono text-gray-500">{{ $historyRun['updated_at'] }}</span>
                                </div>
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded bg-gray-700 text-gray-300">{{ $historyRun['status'] }}</span>
                                    <span class="text-[10px] font-bold text-emerald-400">{{ $historyRun['progress_percent'] }}%</span>
                                </div>
                                <p class="text-xs text-gray-400 truncate">{{ $historyRun['goal'] }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 italic">No previous runs found.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Live Team Presence -->
                <div class="glass-panel overflow-hidden">
                    <h3 class="text-xs font-bold tracking-widest text-gray-500 mb-4">ACTIVE AGENTS ON TASK</h3>
                    <div class="flex flex-wrap gap-2">
                        @forelse($activeTeam as $actorKey)
                            <div class="flex items-center gap-2 rounded-full border border-gray-700 bg-gray-800/50 px-3 py-1.5 shadow">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                <span class="text-xs font-bold text-gray-200">{{ $actorKey }}</span>
                            </div>
                        @empty
                            <p class="text-xs text-gray-500">Awaiting agent assignment...</p>
                        @endforelse
                    </div>
                </div>

                <!-- Threads -->
                <div class="glass-panel overflow-hidden">
                    <h3 class="text-xs font-bold tracking-widest text-gray-500 mb-4">PARALLEL THREADS</h3>
                    <div class="space-y-2">
                        @foreach($workspaceThreads as $thread)
                            <button wire:click="selectThread('{{ $thread['thread_key'] }}')" class="w-full flex items-center justify-between px-4 py-2 rounded-xl text-left transition {{ $selectedThreadKey === $thread['thread_key'] ? 'bg-blue-600/30 border border-blue-500/50 text-white' : 'hover:bg-gray-800/80 text-gray-400 border border-transparent' }}">
                                <span class="text-sm font-bold">{{ $thread['title'] }}</span>
                                <span class="text-xs font-mono bg-gray-900 px-2 py-1 rounded">{{ $thread['events_count'] }} msgs</span>
                            </button>
                        @endforeach
                    </div>
                </div>

            </div>

            <!-- Right Main Area (Terminal & Input) -->
            <div class="w-full lg:w-2/3 flex flex-col gap-6">
                
                <!-- Terminal Output (Events) -->
                <div class="glass-panel flex-1 flex flex-col overflow-hidden min-h-[400px]">
                    <div class="flex items-center justify-between mb-4 pb-2 border-b border-gray-700/50">
                        <h3 class="text-xs font-bold tracking-widest text-gray-500 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                            LIVE TEAM EVENT STREAM
                        </h3>
                        <span class="text-xs text-gray-500 font-mono">{{ count($workspaceEvents) }} packets</span>
                    </div>

                    <!-- Event Feed -->
                    <div class="flex-1 overflow-y-auto space-y-4 pr-2" id="chat-feed" style="max-height: 500px;">
                        @forelse($workspaceEvents as $event)
                            <div class="flex gap-4 items-start pb-4 border-b border-gray-800/50 last:border-0 relative group">
                                <div class="mt-1 shadow flex shrink-0 h-8 w-8 items-center justify-center rounded-lg border {{ $event['actor_type'] === 'user' ? 'border-blue-500/50 bg-blue-900/30 text-blue-400' : 'border-emerald-500/50 bg-emerald-900/30 text-emerald-400' }}">
                                    @if($event['actor_type'] === 'user')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                    @else
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex justify-between items-baseline mb-1">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-bold {{ $event['actor_type'] === 'user' ? 'text-blue-200' : 'text-emerald-200' }}">
                                                {{ $event['actor_key'] }}
                                            </span>
                                            <span class="text-[10px] text-gray-500 uppercase tracking-widest bg-gray-800 px-1.5 py-0.5 rounded">{{ str_replace('_', ' ', $event['event_type']) }}</span>
                                        </div>
                                        <span class="text-[10px] text-gray-600 font-mono">{{ $event['at'] }}</span>
                                    </div>
                                    <div class="text-sm text-gray-300 leading-relaxed font-mono mt-2" style="white-space: pre-wrap; word-break: break-word;">{{ $event['message'] }}</div>
                                    
                                    <!-- Approval Mechanism -->
                                    @if(isset($event['payload']['requires_approval']) && $event['payload']['requires_approval'] === true && isset($event['payload']['step_id']))
                                        <div class="mt-4 p-4 bg-yellow-900/20 border border-yellow-700/50 rounded-xl">
                                            <p class="text-sm font-bold text-yellow-500 mb-2 flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                                Agent requires approval for destructive action.
                                            </p>
                                            <div class="text-xs bg-black/50 p-3 rounded-lg border border-gray-800 text-gray-400 font-mono mb-3 overflow-x-auto">
                                                @json($event['payload']['tool_calls'] ?? [], JSON_PRETTY_PRINT)
                                            </div>
                                            <button wire:click="approveStep({{ $event['payload']['step_id'] }})" class="px-4 py-2 bg-yellow-600 hover:bg-yellow-500 text-white text-xs font-bold rounded shadow transition">
                                                Approve Tool Execution
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="h-full flex items-center justify-center text-gray-500 text-sm">
                                No logs available. Send a command to start.
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Command Input Area -->
                <div class="glass-panel">
                    <form wire:submit.prevent="sendMessage" class="flex items-center gap-3">
                        <input wire:model.defer="message" 
                               type="text" 
                               placeholder="Type command for the Director Agent..." 
                               class="flex-1 bg-gray-800/80 border border-gray-700 text-white rounded-xl px-4 py-3 placeholder-gray-500 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition shadow-inner">
                        <button type="submit" 
                                class="bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 px-6 rounded-xl transition shadow flex items-center gap-2"
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50 cursor-not-allowed">
                            <span wire:loading.remove>Deploy</span>
                            <span wire:loading>Sending...</span>
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('livewire:load', function () {
            Livewire.on('message-sent', () => {
                const feed = document.getElementById('chat-feed');
                if (feed) {
                    feed.scrollTop = feed.scrollHeight;
                }
            });
        });
    </script>
</x-filament::page>
