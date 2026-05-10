<?php

return [
    'decimals' => 2,
    'cache_ttl' => 60,
    'slow_threshold_ms' => env('PRICING_SLOW_THRESHOLD', 200),
    'feature_flag' => 'enable_dynamic_pricing',
];
