<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Agency Agents Configuration
    |--------------------------------------------------------------------------
    */

    'enabled' => env('AGENCY_AGENTS_ENABLED', true),

    'chief_agent_slug' => env('AGENCY_AGENTS_CHIEF_SLUG', 'director-agent'),
    'multi_delegate_count' => (int) env('AGENCY_AGENTS_MULTI_DELEGATE_COUNT', 6),
    'auto_execute_tasks' => env('AGENCY_AGENTS_AUTO_EXECUTE_TASKS', true),
    'execution_mode' => env('AGENCY_AGENTS_EXECUTION_MODE', 'sync'), // sync|queue

    /*
    |--------------------------------------------------------------------------
    | Agent Categories
    |--------------------------------------------------------------------------
    */
    'categories' => [
        'academic' => [
            'name' => 'Academic',
            'description' => 'Academic and research specialists',
            'color' => 'blue',
            'icon' => 'СЂСџР‹вЂњ',
            'sector' => ['x_min' => 0, 'x_max' => 150, 'y_min' => 0, 'y_max' => 200],
        ],
        'design' => [
            'name' => 'Design',
            'description' => 'UI/UX and visual design specialists',
            'color' => 'purple',
            'icon' => 'СЂСџР‹РЃ',
            'sector' => ['x_min' => 160, 'x_max' => 310, 'y_min' => 0, 'y_max' => 200],
        ],
        'engineering' => [
            'name' => 'Engineering',
            'description' => 'Software development and architecture',
            'color' => 'green',
            'icon' => 'РІС™в„ўРїС‘РЏ',
            'sector' => ['x_min' => 320, 'x_max' => 470, 'y_min' => 0, 'y_max' => 200],
        ],
        'game-development' => [
            'name' => 'Game Development',
            'description' => 'Game design and development',
            'color' => 'red',
            'icon' => 'СЂСџР‹В®',
            'sector' => ['x_min' => 480, 'x_max' => 600, 'y_min' => 0, 'y_max' => 200],
        ],
        'marketing' => [
            'name' => 'Marketing',
            'description' => 'Marketing and growth specialists',
            'color' => 'pink',
            'icon' => 'СЂСџвЂњСћ',
            'sector' => ['x_min' => 0, 'x_max' => 150, 'y_min' => 210, 'y_max' => 400],
        ],
        'paid-media' => [
            'name' => 'Paid Media',
            'description' => 'Paid advertising specialists',
            'color' => 'yellow',
            'icon' => 'СЂСџвЂ™В°',
            'sector' => ['x_min' => 160, 'x_max' => 310, 'y_min' => 210, 'y_max' => 400],
        ],
        'product' => [
            'name' => 'Product',
            'description' => 'Product management and strategy',
            'color' => 'indigo',
            'icon' => 'СЂСџвЂњВ¦',
            'sector' => ['x_min' => 320, 'x_max' => 470, 'y_min' => 210, 'y_max' => 400],
        ],
        'project-management' => [
            'name' => 'Project Management',
            'description' => 'Project coordination and delivery',
            'color' => 'teal',
            'icon' => 'СЂСџвЂњвЂ№',
            'sector' => ['x_min' => 480, 'x_max' => 600, 'y_min' => 210, 'y_max' => 400],
        ],
        'sales' => [
            'name' => 'Sales',
            'description' => 'Sales and business development',
            'color' => 'orange',
            'icon' => 'СЂСџвЂ™С',
            'sector' => ['x_min' => 0, 'x_max' => 200, 'y_min' => 0, 'y_max' => 150],
        ],
        'testing' => [
            'name' => 'Testing',
            'description' => 'QA and validation specialists',
            'color' => 'red',
            'icon' => '🧪',
            'sector' => ['x_min' => 210, 'x_max' => 400, 'y_min' => 160, 'y_max' => 300],
        ],
        'support' => [
            'name' => 'Support',
            'description' => 'Support operations and governance',
            'color' => 'slate',
            'icon' => '🛟',
            'sector' => ['x_min' => 410, 'x_max' => 600, 'y_min' => 160, 'y_max' => 300],
        ],        'specialized' => [
            'name' => 'Specialized',
            'description' => 'Specialized domain experts',
            'color' => 'cyan',
            'icon' => 'СЂСџвЂќВ§',
            'sector' => ['x_min' => 210, 'x_max' => 400, 'y_min' => 0, 'y_max' => 150],
        ],
        'strategy' => [
            'name' => 'Strategy',
            'description' => 'Strategic planning and execution',
            'color' => 'violet',
            'icon' => 'СЂСџР‹Р‡',
            'sector' => ['x_min' => 410, 'x_max' => 600, 'y_min' => 0, 'y_max' => 150],
        ],
        'spatial-computing' => [
            'name' => 'Spatial Computing',
            'description' => 'AR/VR and spatial technology',
            'color' => 'lime',
            'icon' => 'СЂСџвЂ“ТђРїС‘РЏ',
            'sector' => ['x_min' => 0, 'x_max' => 200, 'y_min' => 160, 'y_max' => 300],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Office Zones Configuration
    |--------------------------------------------------------------------------
    */
    'office_zones' => [
        'workspace' => [
            'name' => 'Р В Р В°Р В±Р С•РЎвЂЎР В°РЎРЏ Р В·Р С•Р Р…Р В°',
            'icon' => 'СЂСџвЂ™С',
            'color' => '#e3f2fd',
            'bounds' => ['x_min' => 0, 'x_max' => 600, 'y_min' => 0, 'y_max' => 400],
            'capacity' => 50,
            'amenities' => ['desks', 'monitors', 'chairs', 'power_outlets'],
        ],
        'meeting_room' => [
            'name' => 'Р СџР ВµРЎР‚Р ВµР С–Р С•Р Р†Р С•РЎР‚Р Р…Р В°РЎРЏ',
            'icon' => 'СЂСџВ¤Сњ',
            'color' => '#fff3e0',
            'bounds' => ['x_min' => 620, 'x_max' => 800, 'y_min' => 0, 'y_max' => 200],
            'capacity' => 12,
            'amenities' => ['conference_table', 'whiteboard', 'projector', 'video_conf'],
        ],
        'brainstorm' => [
            'name' => 'Р вЂ”Р С•Р Р…Р В° Р СР С•Р В·Р С–Р С•Р Р†Р С•Р С–Р С• РЎв‚¬РЎвЂљРЎС“РЎР‚Р СР В°',
            'icon' => 'СЂСџвЂ™РЋ',
            'color' => '#f3e5f5',
            'bounds' => ['x_min' => 620, 'x_max' => 800, 'y_min' => 220, 'y_max' => 400],
            'capacity' => 15,
            'amenities' => ['whiteboards', 'sticky_notes', 'markers', 'comfortable_seating'],
        ],
        'break_room' => [
            'name' => 'Р вЂ”Р С•Р Р…Р В° Р С•РЎвЂљР Т‘РЎвЂ№РЎвЂ¦Р В°',
            'icon' => 'СЂСџвЂєвЂ№РїС‘РЏ',
            'color' => '#e8f5e9',
            'bounds' => ['x_min' => 0, 'x_max' => 300, 'y_min' => 420, 'y_max' => 580],
            'capacity' => 20,
            'amenities' => ['sofas', 'plants', 'games', 'relaxation_area'],
        ],
        'cafeteria' => [
            'name' => 'Р РЋРЎвЂљР С•Р В»Р С•Р Р†Р В°РЎРЏ',
            'icon' => 'СЂСџРЊР…РїС‘РЏ',
            'color' => '#fff8e1',
            'bounds' => ['x_min' => 320, 'x_max' => 600, 'y_min' => 420, 'y_max' => 580],
            'capacity' => 30,
            'amenities' => ['tables', 'vending_machines', 'microwave', 'refrigerator'],
        ],
        'lounge' => [
            'name' => 'Р вЂєР В°РЎС“Р Р…Р В¶ Р В·Р С•Р Р…Р В°',
            'icon' => 'РІВвЂў',
            'color' => '#fce4ec',
            'bounds' => ['x_min' => 620, 'x_max' => 800, 'y_min' => 420, 'y_max' => 580],
            'capacity' => 15,
            'amenities' => ['coffee_machine', 'comfortable_chairs', 'magazines', 'quiet_area'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    */
    'performance' => [
        'default_score' => 50,
        'max_score' => 100,
        'min_score' => 0,
        'update_interval' => 3600, // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Communication Settings
    |--------------------------------------------------------------------------
    */
    'communication' => [
        'max_message_length' => 5000,
        'retention_days' => 30,
        'enable_broadcasts' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring Settings
    |--------------------------------------------------------------------------
    */
    'monitoring' => [
        'enabled' => true,
        'metrics_retention_days' => 90,
        'alert_thresholds' => [
            'low_performance' => 30,
            'stuck_task_hours' => 24,
            'unread_messages' => 10,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 2D Office Settings
    |--------------------------------------------------------------------------
    */
    'office_2d' => [
        'enabled' => true,
        'update_interval' => 3000, // milliseconds
        'max_agents_display' => 220,
        'avatar_size' => 32,
        'office_dimensions' => [
            'width' => 800,
            'height' => 600,
        ],
        'pixel_avatar_size' => 32,
        'movement_speed' => 2, // pixels per frame
        'enable_animations' => true,
        'enable_drag_drop' => true,
        'enable_heatmap' => true,
        'enable_minimap' => true,
        'target_population' => 170,
        'shared_api_key' => env('AGENCY_AGENTS_SHARED_API_KEY', '7eef7b1f69e2491c9311220b9637a8d9.kRWOvi5BufUwr4wl'),
    ],

    /*
    |--------------------------------------------------------------------------
    | WebSocket Settings
    |--------------------------------------------------------------------------
    */
    'websocket' => [
        'enabled' => true,
        'host' => env('WEBSOCKET_HOST', '127.0.0.1'),
        'port' => env('WEBSOCKET_PORT', 6001),
        'update_interval' => 1000, // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | File Paths
    |--------------------------------------------------------------------------
    */
    'paths' => [
        'agents_source' => env('AGENCY_AGENTS_SOURCE_PATH', is_dir(base_path('agency-agents')) ? base_path('agency-agents') : dirname(base_path()).'/agency-agents'),
        'avatars_storage' => storage_path('app/public/agent-avatars'),
        'pixel_sprites' => storage_path('app/public/pixel-sprites'),
        'logs' => storage_path('logs/agency-agents'),
    ],
    'logistics_operations' => [
        'roles' => [
            'agents-orchestrator' => ['role' => 'Logistics Orchestrator', 'modules' => ['logistics', 'orders'], 'access_level' => 'admin', 'priority' => 100],
            'specialized-workflow-architect' => ['role' => 'Dispatch Workflow Architect', 'modules' => ['logistics', 'orders'], 'access_level' => 'admin', 'priority' => 96],
            'engineering-backend-architect' => ['role' => 'Order Integration Lead', 'modules' => ['orders', 'customers'], 'access_level' => 'write', 'priority' => 94],
            'engineering-data-engineer' => ['role' => 'Tracking Data Engineer', 'modules' => ['logistics', 'map'], 'access_level' => 'write', 'priority' => 92],
            'engineering-database-optimizer' => ['role' => 'Spatial Database Custodian', 'modules' => ['warehouse', 'map'], 'access_level' => 'write', 'priority' => 91],
            'engineering-sre' => ['role' => 'Realtime Reliability Commander', 'modules' => ['logistics', 'fleet'], 'access_level' => 'admin', 'priority' => 90],
            'engineering-devops-automator' => ['role' => 'WebSocket Delivery Operator', 'modules' => ['logistics', 'map'], 'access_level' => 'write', 'priority' => 89],
            'project-manager-senior' => ['role' => 'Dispatch Program Manager', 'modules' => ['orders', 'customers'], 'access_level' => 'write', 'priority' => 88],
            'project-management-studio-operations' => ['role' => 'Courier Operations Manager', 'modules' => ['fleet', 'map'], 'access_level' => 'write', 'priority' => 87],
            'testing-api-tester' => ['role' => 'API Contract Watcher', 'modules' => ['orders', 'customers'], 'access_level' => 'observe', 'priority' => 70],
            'testing-performance-benchmarker' => ['role' => 'ETA Performance Analyst', 'modules' => ['map', 'fleet'], 'access_level' => 'observe', 'priority' => 69],
            'support-analytics-reporter' => ['role' => 'Delivery Intelligence Reporter', 'modules' => ['logistics', 'customers'], 'access_level' => 'observe', 'priority' => 68],
            'support-infrastructure-maintainer' => ['role' => 'Infrastructure Caretaker', 'modules' => ['fleet', 'warehouse'], 'access_level' => 'write', 'priority' => 67],
            'engineering-mobile-app-builder' => ['role' => 'Courier Mobile Lead', 'modules' => ['customers', 'map'], 'access_level' => 'write', 'priority' => 86],
            'design-ux-architect' => ['role' => 'Customer Tracking UX Lead', 'modules' => ['customers', 'map'], 'access_level' => 'write', 'priority' => 66],
            'engineering-frontend-developer' => ['role' => 'Operations Console Builder', 'modules' => ['logistics', 'warehouse'], 'access_level' => 'write', 'priority' => 65],
            'support-support-responder' => ['role' => 'Customer Escalation Responder', 'modules' => ['customers', 'orders'], 'access_level' => 'write', 'priority' => 64],
            'supply-chain-strategist' => ['role' => 'Warehouse Flow Strategist', 'modules' => ['warehouse', 'fleet'], 'access_level' => 'admin', 'priority' => 85],
            'automation-governance-architect' => ['role' => 'Automation Governance Lead', 'modules' => ['logistics', 'fleet'], 'access_level' => 'admin', 'priority' => 84],
            'compliance-auditor' => ['role' => 'Audit and Compliance Agent', 'modules' => ['orders', 'warehouse'], 'access_level' => 'observe', 'priority' => 63],
            'lsp-index-engineer' => ['role' => 'Knowledge Routing Indexer', 'modules' => ['logistics', 'customers'], 'access_level' => 'observe', 'priority' => 62],
            'engineering-autonomous-optimization-architect' => ['role' => 'Route Optimization Agent', 'modules' => ['map', 'fleet'], 'access_level' => 'write', 'priority' => 83],
            'engineering-code-reviewer' => ['role' => 'Rules Integrity Reviewer', 'modules' => ['orders', 'logistics'], 'access_level' => 'observe', 'priority' => 61],
            'testing-evidence-collector' => ['role' => 'Delivery Evidence Collector', 'modules' => ['customers', 'warehouse'], 'access_level' => 'observe', 'priority' => 60],
        ],
    ],
];



