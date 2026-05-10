<?php

return [
    'enabled' => env('AGENT_OS_ENABLED', true),
    'execution_mode' => env('AGENT_OS_EXECUTION_MODE', 'sync'),
    'loop' => [
        'max_iterations' => (int) env('AGENT_OS_LOOP_MAX_ITERATIONS', 50),
    ],

    'feature_flags' => [
        'tool_browser' => env('AGENT_OS_TOOL_BROWSER', false),
        'tool_research' => env('AGENT_OS_TOOL_RESEARCH', false),
        'tool_code' => env('AGENT_OS_TOOL_CODE', false),
        'deploy_staging' => env('AGENT_OS_DEPLOY_STAGING', false),
        'deploy_production' => env('AGENT_OS_DEPLOY_PRODUCTION', false),
    ],
    'tool_fallback' => [
        'enabled' => env('AGENT_OS_TOOL_FALLBACK_ENABLED', true),
    ],
    'audit' => [
        'auto_followup_on_findings' => env('AGENT_OS_AUTO_FOLLOWUP_ON_FINDINGS', true),
        'min_findings_for_followup' => (int) env('AGENT_OS_MIN_FINDINGS_FOR_FOLLOWUP', 1),
    ],
    'chat' => [
        'async_enabled' => env('AGENT_OS_CHAT_ASYNC_ENABLED', true),
        'connection' => env('AGENT_OS_CHAT_CONNECTION', 'redis'),
        'queue' => 'default',
        'legacy_bridge_enabled' => env('AGENT_OS_CHAT_LEGACY_BRIDGE_ENABLED', false),
        'sync_fallback_on_dispatch_fail' => env('AGENT_OS_CHAT_SYNC_FALLBACK_ON_DISPATCH_FAIL', false),
        'poll_interval_seconds' => (int) env('AGENT_OS_CHAT_POLL_INTERVAL_SECONDS', 5),
        'planning_stale_seconds' => (int) env('AGENT_OS_PLANNING_STALE_SECONDS', 20),
    ],
    'api' => [
        'async_enabled' => env('AGENT_OS_API_ASYNC_ENABLED', true),
        'connection' => env('AGENT_OS_API_CONNECTION', env('AGENT_OS_CHAT_CONNECTION', 'redis')),
        'queue' => 'default',
    ],

    'timeout' => [
        'heartbeat_grace_minutes' => (int) env('AGENT_OS_HEARTBEAT_GRACE_MINUTES', 2),
        'default_step_minutes' => (int) env('AGENT_OS_DEFAULT_STEP_TIMEOUT_MINUTES', 15),
    ],
    'memory' => [
        'dedup_window_seconds' => (int) env('AGENT_OS_MEMORY_DEDUP_WINDOW_SECONDS', 120),
        'context_limit' => (int) env('AGENT_OS_MEMORY_CONTEXT_LIMIT', 30),
        'instructions' => [
            'enabled' => env('AGENT_OS_MEMORY_INSTRUCTIONS_ENABLED', true),
            'repo_path' => env('AGENT_OS_MEMORY_INSTRUCTIONS_REPO_PATH', base_path('agency-agents')),
            'profiles' => [
                'coordinator' => [
                    'specialized/agents-orchestrator.md',
                    'support/support-support-responder.md',
                    'specialized/identity-graph-operator.md',
                    'engineering/engineering-autonomous-optimization-architect.md',
                    'specialized/zk-steward.md',
                ],
                'worker:security_review' => [
                    'specialized/blockchain-security-auditor.md',
                    'engineering/engineering-threat-detection-engineer.md',
                ],
                'worker:ui_accessibility_review' => [
                    'testing/testing-accessibility-auditor.md',
                    'design/design-ux-researcher.md',
                ],
                'worker:testing_cicd_review' => [
                    'testing/testing-api-tester.md',
                    'testing/testing-test-results-analyzer.md',
                ],
            ],
        ],
        'files' => [
            'enabled' => env('AGENT_OS_MEMORY_FILES_ENABLED', true),
            'root_path' => env('AGENT_OS_MEMORY_FILES_ROOT_PATH', storage_path('agent-memory')),
            'daily_notes_pattern' => 'memory/%s.md',
            'evergreen_file' => 'MEMORY.md',
            'open_loops_file' => 'OPEN_LOOPS.md',
        ],
        'external' => [
            'enabled' => env('AGENT_OS_MEMORY_EXTERNAL_ENABLED', false),
            'base_url' => env('AGENT_OS_MEMORY_EXTERNAL_BASE_URL'),
            'token' => env('AGENT_OS_MEMORY_EXTERNAL_TOKEN'),
            'timeout_seconds' => (int) env('AGENT_OS_MEMORY_EXTERNAL_TIMEOUT_SECONDS', 8),
        ],
        'compaction' => [
            'enabled' => env('AGENT_OS_MEMORY_COMPACTION_ENABLED', true),
            'older_than_days' => (int) env('AGENT_OS_MEMORY_COMPACTION_OLDER_THAN_DAYS', 2),
            'keep_recent' => (int) env('AGENT_OS_MEMORY_COMPACTION_KEEP_RECENT', 60),
        ],
    ],
];
