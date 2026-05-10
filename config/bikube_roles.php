<?php

return [
    'roles' => [
        'owner' => ['admin_access', 'dispatch_actions', 'payment_manual_actions', 'payout_approval', 'worker_order_actions', 'partner_order_actions', 'customer_access'],
        'admin' => ['admin_access', 'dispatch_actions', 'payment_manual_actions', 'payout_approval'],
        'ops_admin' => ['admin_access', 'dispatch_actions', 'payment_manual_actions', 'payout_approval'],
        'ops_manager' => ['admin_access', 'dispatch_actions', 'payment_manual_actions'],
        'ops_rules_admin' => ['admin_access', 'dispatch_actions'],
        'operator' => ['admin_access', 'dispatch_actions'],
        'dispatcher' => ['dispatch_actions'],
        'support' => ['admin_access', 'customer_access'],
        'partner' => ['partner_order_actions'],
        'courier' => ['worker_order_actions'],
        'worker' => ['worker_order_actions'],
        'executor' => ['worker_order_actions'],
        'handyman' => ['worker_order_actions'],
        'customer' => ['customer_access'],
    ],
];
