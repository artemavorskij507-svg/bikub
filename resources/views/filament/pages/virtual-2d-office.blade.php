<x-filament::page>
    <style>
        .pixel-shell {
            --bg: #0a1a2f;
            --panel: #10233f;
            --panel-soft: #17345d;
            --line: #2a4f82;
            --ink: #d4e6ff;
            --muted: #9db9de;
            --accent: #7ee787;
            --warn: #ffb86b;
            --danger: #ff6b7a;
            --tile: #1a3255;
            font-family: "Trebuchet MS", "Segoe UI", sans-serif;
            color: var(--ink);
        }

        .pixel-panel {
            border: 2px solid var(--line);
            background: linear-gradient(180deg, var(--panel), var(--panel-soft));
            box-shadow: 0 0 0 2px rgba(9, 22, 44, .85) inset;
            border-radius: 10px;
        }

        .hud-chip {
            border: 1px solid #315f98;
            background: #102949;
            border-radius: 8px;
            padding: .45rem .65rem;
            font-size: 12px;
            color: var(--muted);
        }

        .hud-chip strong {
            color: var(--ink);
            font-size: 14px;
            margin-left: .35rem;
        }

        .office-stage {
            position: relative;
            min-height: 720px;
            background:
                linear-gradient(180deg, #182844 0%, #10233f 42%, #0f2845 100%),
                repeating-linear-gradient(
                    0deg,
                    rgba(255,255,255,.02),
                    rgba(255,255,255,.02) 1px,
                    transparent 1px,
                    transparent 20px
                ),
                repeating-linear-gradient(
                    90deg,
                    rgba(255,255,255,.015),
                    rgba(255,255,255,.015) 1px,
                    transparent 1px,
                    transparent 20px
                );
            overflow: hidden;
        }

        .room-tile {
            position: absolute;
            border: 2px solid rgba(201, 228, 255, .28);
            background: linear-gradient(180deg, rgba(43, 78, 122, .62), rgba(23, 52, 93, .62));
            border-radius: 8px;
            padding: .45rem;
        }

        .room-title {
            font-size: 11px;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #e6f0ff;
            margin-bottom: .35rem;
        }

        .desk-dot {
            width: 12px;
            height: 8px;
            border: 1px solid rgba(220, 239, 255, .25);
            border-radius: 2px;
            background: #173458;
        }

        .pixel-agent {
            position: absolute;
            width: 18px;
            height: 18px;
            border-radius: 2px;
            border: 1px solid rgba(255,255,255,.75);
            box-shadow: 0 0 0 1px rgba(8, 20, 39, .9);
            cursor: pointer;
            transition: transform .12s ease;
        }

        .pixel-agent:hover {
            transform: scale(1.15);
            z-index: 30;
        }

        .pixel-agent::after {
            content: "";
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            bottom: -5px;
            width: 10px;
            height: 3px;
            border-radius: 10px;
            background: rgba(5, 14, 28, .45);
        }

        .agent-busy { animation: pixelPulse .9s ease-in-out infinite; }
        .agent-active { animation: pixelStep .7s steps(2) infinite; }
        .agent-idle { opacity: .82; }

        @keyframes pixelPulse {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-1px); }
        }

        @keyframes pixelStep {
            0%, 100% { transform: translateX(0); }
            50% { transform: translateX(1px); }
        }

        .pixel-minimap {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 6px;
        }

        .pixel-minimap .mini-room {
            border: 1px solid rgba(194, 223, 255, .25);
            border-radius: 6px;
            padding: .35rem;
            font-size: 11px;
            background: rgba(21, 47, 83, .85);
        }

        .pixel-scroll {
            max-height: 260px;
            overflow-y: auto;
            scrollbar-width: thin;
        }

        .pixel-scroll::-webkit-scrollbar { width: 8px; }
        .pixel-scroll::-webkit-scrollbar-thumb { background: #3f699d; border-radius: 8px; }

        .pixel-badge {
            font-size: 10px;
            padding: .1rem .35rem;
            border-radius: 999px;
            border: 1px solid rgba(190, 220, 255, .28);
            background: rgba(27, 57, 97, .8);
            color: #d7e9ff;
        }

        @media (max-width: 1280px) {
            .office-stage { min-height: 600px; }
        }

        @media (max-width: 1024px) {
            .office-stage { min-height: 520px; }
        }
    </style>

    <div class="pixel-shell space-y-4" wire:poll.8s="refreshData">
        @if (! $moduleReady)
            <div class="pixel-panel p-4 text-sm">
                <div class="font-semibold">Agency Agents module is not initialized.</div>
                <div class="text-slate-300">{{ $systemOverview['message'] ?? 'Run migrations for agency tables and reload this page.' }}</div>
            </div>
        @else
            @php
                $rooms = [
                    'development' => ['label' => 'Development Room', 'zones' => ['open_space', 'workspace'], 'left' => 2, 'top' => 7, 'width' => 36, 'height' => 34, 'accent' => '#77b7ff'],
                    'design' => ['label' => 'Design Room', 'zones' => ['design_room', 'brainstorm'], 'left' => 40, 'top' => 7, 'width' => 26, 'height' => 34, 'accent' => '#f9a8d4'],
                    'testing' => ['label' => 'Testing Room', 'zones' => ['testing', 'qa_room'], 'left' => 68, 'top' => 7, 'width' => 30, 'height' => 34, 'accent' => '#fcd34d'],
                    'analytics' => ['label' => 'Analytics Room', 'zones' => ['lounge', 'relax_zone', 'cafeteria'], 'left' => 2, 'top' => 43, 'width' => 32, 'height' => 32, 'accent' => '#a7f3d0'],
                    'server' => ['label' => 'Server Room', 'zones' => ['server_room'], 'left' => 36, 'top' => 43, 'width' => 28, 'height' => 32, 'accent' => '#93c5fd'],
                    'meeting' => ['label' => 'Meeting Room', 'zones' => ['meeting_room', 'kitchen'], 'left' => 66, 'top' => 43, 'width' => 32, 'height' => 32, 'accent' => '#fca5a5'],
                ];

                $roomAgents = collect($rooms)->map(fn () => collect());

                foreach ($agents as $agent) {
                    $zone = (string) ($agent['current_zone'] ?? '');
                    $roomKey = collect($rooms)->first(function ($room) use ($zone) {
                        return in_array($zone, $room['zones'], true);
                    });

                    $key = $roomKey ? array_search($roomKey, $rooms, true) : 'development';
                    $roomAgents[$key] = $roomAgents[$key]->push($agent);
                }

                $pendingCount = count($taskBoard['pending'] ?? []);
                $inProgressCount = count($taskBoard['in_progress'] ?? []);
                $tokenBurn = number_format(max(0.01, (($systemOverview['communications']['total_messages'] ?? 0) / 200)), 2);
            @endphp

            <div class="pixel-panel p-3">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold tracking-wide">Virtual Pixel Office</h2>
                        <p class="text-xs text-slate-300">Retro 2D command center: all agents execute through Director orchestration.</p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <x-filament::button wire:click="refreshData" size="sm" color="gray">Refresh</x-filament::button>
                        <x-filament::button wire:click="toggleDirectorOnlyMode" size="sm" color="{{ $directorOnlyMode ? 'success' : 'warning' }}">Director Mode: {{ $directorOnlyMode ? 'ON' : 'OFF' }}</x-filament::button>
                        <x-filament::button wire:click="setSimulationSpeed(1)" size="sm" color="{{ $simulationSpeed === 1 ? 'primary' : 'gray' }}">1x</x-filament::button>
                        <x-filament::button wire:click="setSimulationSpeed(2)" size="sm" color="{{ $simulationSpeed === 2 ? 'primary' : 'gray' }}">2x</x-filament::button>
                        <x-filament::button wire:click="setSimulationSpeed(4)" size="sm" color="{{ $simulationSpeed === 4 ? 'primary' : 'gray' }}">4x</x-filament::button>
                        <x-filament::button wire:click="toggleHeatmap" size="sm" color="{{ $showHeatmap ? 'danger' : 'gray' }}">Heatmap</x-filament::button>
                        <x-filament::button wire:click="toggleMinimap" size="sm" color="{{ $showMinimap ? 'primary' : 'gray' }}">Minimap</x-filament::button>
                    </div>
                </div>

                <div class="mt-3 grid grid-cols-2 gap-2 md:grid-cols-5">
                    <div class="hud-chip">Population<strong>{{ $officePopulation }}</strong></div>
                    <div class="hud-chip">Queue<strong>{{ $pendingCount }}</strong></div>
                    <div class="hud-chip">In Progress<strong>{{ $inProgressCount }}</strong></div>
                    <div class="hud-chip">Token Burn<strong>${{ $tokenBurn }}/min</strong></div>
                    <div class="hud-chip">Time Scale<strong>{{ $simulationSpeed }}x</strong></div>
                </div>

                @if($directorOnlyMode)
                    <div class="mt-3 rounded border border-emerald-300/40 bg-emerald-500/10 px-3 py-2 text-xs text-emerald-100">
                        Calm mode is active: autonomous noise is reduced, and directives flow through Director -> Teams -> QA reports.
                    </div>
                @endif
            </div>

            <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
                <div class="space-y-4 xl:col-span-8">
                    <div class="pixel-panel p-3">
                        <div class="office-stage rounded">
                            @foreach($rooms as $key => $room)
                                <div class="room-tile" style="left: {{ $room['left'] }}%; top: {{ $room['top'] }}%; width: {{ $room['width'] }}%; height: {{ $room['height'] }}%; border-color: {{ $room['accent'] }}66;">
                                    <button
                                        type="button"
                                        wire:click="selectZone('{{ $room['zones'][0] }}')"
                                        class="room-title"
                                        style="color: {{ $room['accent'] }}"
                                    >
                                        {{ $room['label'] }}
                                    </button>

                                    <div class="grid grid-cols-6 gap-1 opacity-70">
                                        @for($d = 0; $d < 12; $d++)
                                            <div class="desk-dot"></div>
                                        @endfor
                                    </div>

                                    @foreach(($roomAgents[$key] ?? collect()) as $agent)
                                        @php
                                            $i = $loop->index;
                                            $col = $i % 8;
                                            $row = intdiv($i, 8);
                                            $left = 5 + ($col * 11);
                                            $top = 36 + ($row * 18);
                                            $statusClass = $agent['status'] === 'busy' ? 'agent-busy' : ($agent['status'] === 'active' ? 'agent-active' : 'agent-idle');
                                        @endphp
                                        <button
                                            type="button"
                                            class="pixel-agent {{ $statusClass }}"
                                            style="left: {{ $left }}px; top: {{ $top }}px; background: {{ $agent['color'] }};"
                                            wire:click="selectAgent({{ $agent['id'] }})"
                                            title="{{ $agent['name'] }} � {{ $agent['current_activity'] }}"
                                        ></button>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>

                    @if($showMinimap)
                        <div class="pixel-panel p-3">
                            <div class="mb-2 text-xs uppercase tracking-wider text-slate-300">Office Mini-Map</div>
                            <div class="pixel-minimap">
                                @foreach($rooms as $key => $room)
                                    <button type="button" wire:click="selectZone('{{ $room['zones'][0] }}')" class="mini-room text-left">
                                        <div class="font-semibold" style="color: {{ $room['accent'] }}">{{ $room['label'] }}</div>
                                        <div class="text-slate-300">Agents: {{ ($roomAgents[$key] ?? collect())->count() }}</div>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div class="space-y-4 xl:col-span-4">
                    <div class="pixel-panel p-3">
                        <div class="mb-2 text-xs uppercase tracking-wider text-slate-300">Inspector</div>
                        @if($selectedAgent)
                            <div class="space-y-1 text-sm">
                                <div class="font-semibold text-white">{{ $selectedAgent['name'] }}</div>
                                <div class="text-slate-300">Role: {{ $selectedAgent['category'] }}</div>
                                <div>Status: <span class="pixel-badge">{{ $selectedAgent['status'] }}</span> <span class="pixel-badge">{{ $selectedAgent['activity'] }}</span></div>
                                <div>Team: {{ $selectedAgent['team'] }}</div>
                                <div>Zone: {{ $selectedAgent['zone'] }} ({{ $selectedAgent['x'] }}, {{ $selectedAgent['y'] }})</div>
                                <div>Tasks: {{ $selectedAgent['task_count'] }} � Messages: {{ $selectedAgent['message_count'] }}</div>
                                <div class="text-slate-400">{{ $selectedAgent['message'] }}</div>
                            </div>
                        @else
                            <div class="text-sm text-slate-300">Select any pixel agent on the map.</div>
                        @endif

                        <div class="mt-3 border-t border-slate-500/40 pt-2 text-xs text-slate-300">
                            @if($selectedZone)
                                <div class="font-semibold text-white">{{ $selectedZone['display_name'] }}</div>
                                <div>Occupancy: {{ $selectedZone['occupancy'] }}/{{ $selectedZone['capacity'] }} ({{ $selectedZone['percentage'] }}%)</div>
                                <div>Amenities: {{ implode(', ', $selectedZone['amenities']) }}</div>
                            @else
                                <div>Select room to inspect occupancy and amenities.</div>
                            @endif
                        </div>
                    </div>

                    <div class="pixel-panel p-3">
                        <div class="mb-2 text-xs uppercase tracking-wider text-slate-300">Director Console</div>
                        <div class="space-y-2">
                            <input type="text" wire:model.defer="directorTaskTitle" class="w-full rounded border-slate-600 bg-slate-900/60 text-sm text-slate-100" placeholder="Task title">
                            <input type="url" wire:model.defer="directorTaskTargetUrl" class="w-full rounded border-slate-600 bg-slate-900/60 text-sm text-slate-100" placeholder="Target URL">
                            <textarea wire:model.defer="directorTaskDescription" rows="3" class="w-full rounded border-slate-600 bg-slate-900/60 text-sm text-slate-100" placeholder="Acceptance criteria for teams..."></textarea>
                            <x-filament::button wire:click="submitDirectorTask" size="sm" color="primary">Assign Task Chain</x-filament::button>
                        </div>

                        <div class="mt-3 flex gap-2">
                            <input type="text" wire:model.defer="directorMessage" class="w-full rounded border-slate-600 bg-slate-900/60 text-sm text-slate-100" placeholder="Direct instruction to Director...">
                            <x-filament::button wire:click="sendMessageToDirector" size="sm" color="gray">Send</x-filament::button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
                <div class="space-y-4 xl:col-span-4">
                    <div class="pixel-panel p-3">
                        <div class="mb-2 text-xs uppercase tracking-wider text-slate-300">Task Board</div>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div>
                                <div class="mb-1 font-semibold text-amber-200">Pending</div>
                                <div class="pixel-scroll space-y-1">
                                    @foreach(($taskBoard['pending'] ?? []) as $task)
                                        <div class="rounded border border-slate-600/60 bg-slate-900/40 p-1.5">
                                            <div class="font-semibold text-slate-100">{{ $task['title'] }}</div>
                                            <div class="text-slate-300">{{ $task['agent'] }} � {{ $task['priority'] }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div>
                                <div class="mb-1 font-semibold text-blue-200">In Progress</div>
                                <div class="pixel-scroll space-y-1">
                                    @foreach(($taskBoard['in_progress'] ?? []) as $task)
                                        <div class="rounded border border-slate-600/60 bg-slate-900/40 p-1.5">
                                            <div class="font-semibold text-slate-100">{{ $task['title'] }}</div>
                                            <div class="text-slate-300">{{ $task['agent'] }} � {{ $task['progress'] }}%</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pixel-panel p-3">
                        <div class="mb-2 text-xs uppercase tracking-wider text-slate-300">QA Report Summary</div>
                        <div class="text-xs text-slate-300 space-y-1">
                            <div>Completed validations: {{ count($taskBoard['completed'] ?? []) }}</div>
                            <div>Failed validations: {{ count($taskBoard['failed'] ?? []) }}</div>
                            <div>Recommendation priority: {{ count($taskBoard['failed'] ?? []) > 0 ? 'High' : 'Normal' }}</div>
                        </div>
                    </div>
                </div>

                <div class="space-y-4 xl:col-span-4">
                    <div class="pixel-panel p-3">
                        <div class="mb-2 text-xs uppercase tracking-wider text-slate-300">Agent Chat Feed</div>
                        <div class="pixel-scroll space-y-1 text-xs">
                            @foreach(array_slice($liveChat, 0, 30) as $message)
                                <div class="rounded border border-slate-600/60 bg-slate-900/40 p-1.5">
                                    <div class="font-semibold text-slate-100">{{ $message['from'] }} -> {{ $message['to'] }} <span class="text-slate-400">[{{ $message['channel'] }}]</span></div>
                                    <div class="text-slate-300">{{ $message['content'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="pixel-panel p-3">
                        <div class="mb-2 text-xs uppercase tracking-wider text-slate-300">Director Backlog</div>
                        <div class="pixel-scroll space-y-1 text-xs">
                            @foreach($directorBacklog as $task)
                                <div class="rounded border border-slate-600/60 bg-slate-900/40 p-1.5">
                                    <div class="font-semibold text-slate-100">{{ $task['title'] }}</div>
                                    <div class="text-slate-300">Status: {{ $task['status'] }} � Priority: {{ $task['priority'] }}</div>
                                    @if($task['iteration'])
                                        <div class="text-slate-400">Iteration #{{ $task['iteration'] }}</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="space-y-4 xl:col-span-4">
                    <div class="pixel-panel p-3">
                        <div class="mb-2 text-xs uppercase tracking-wider text-slate-300">Notifications</div>
                        <div class="pixel-scroll space-y-1 text-xs">
                            @foreach($liveNotifications as $notification)
                                <div class="rounded border border-slate-600/60 bg-slate-900/40 p-1.5">
                                    <div class="font-semibold text-slate-100">{{ $notification['title'] }} � {{ $notification['actor'] }}</div>
                                    <div class="text-slate-300">{{ $notification['text'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="pixel-panel p-3">
                        <div class="mb-2 text-xs uppercase tracking-wider text-slate-300">Meeting Timeline</div>
                        <div class="pixel-scroll space-y-1 text-xs">
                            @foreach($meetingTimeline as $meeting)
                                <div class="rounded border border-slate-600/60 bg-slate-900/40 p-1.5">
                                    <div class="font-semibold text-slate-100">{{ $meeting['event'] }} � {{ $meeting['host'] }}</div>
                                    <div class="text-slate-300">{{ $meeting['zone'] }} � {{ $meeting['time'] }}</div>
                                    <div class="text-slate-400">{{ $meeting['description'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament::page>