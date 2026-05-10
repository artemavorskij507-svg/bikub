<?php

return [
    'providers' => [
        'bankid' => [
            'display_name' => 'BankID',
            'client_id' => env('EID_BANKID_CLIENT_ID'),
            'client_secret' => env('EID_BANKID_CLIENT_SECRET'),
            'redirect_uri' => env('EID_BANKID_REDIRECT_URI', 'https://your-domain.com/auth/eid/bankid/callback'),
            'issuer' => env('EID_BANKID_ISSUER'),
            'authorization_endpoint' => env('EID_BANKID_AUTH_URL'),
            'token_endpoint' => env('EID_BANKID_TOKEN_URL'),
            'userinfo_endpoint' => env('EID_BANKID_USERINFO_URL'),
            'scope' => env('EID_BANKID_SCOPE', 'openid profile email'),
        ],

        'minid' => [
            'display_name' => 'MinID',
            'client_id' => env('EID_MINID_CLIENT_ID'),
            'client_secret' => env('EID_MINID_CLIENT_SECRET'),
            'redirect_uri' => env('EID_MINID_REDIRECT_URI', 'https://your-domain.com/auth/eid/minid/callback'),
            'issuer' => env('EID_MINID_ISSUER'),
            'authorization_endpoint' => env('EID_MINID_AUTH_URL'),
            'token_endpoint' => env('EID_MINID_TOKEN_URL'),
            'userinfo_endpoint' => env('EID_MINID_USERINFO_URL'),
            'scope' => env('EID_MINID_SCOPE', 'openid profile email'),
        ],

        'buypass' => [
            'display_name' => 'Buypass ID',
            'client_id' => env('EID_BUYPASS_CLIENT_ID'),
            'client_secret' => env('EID_BUYPASS_CLIENT_SECRET'),
            'redirect_uri' => env('EID_BUYPASS_REDIRECT_URI', 'https://your-domain.com/auth/eid/buypass/callback'),
            'issuer' => env('EID_BUYPASS_ISSUER'),
            'authorization_endpoint' => env('EID_BUYPASS_AUTH_URL'),
            'token_endpoint' => env('EID_BUYPASS_TOKEN_URL'),
            'userinfo_endpoint' => env('EID_BUYPASS_USERINFO_URL'),
            'scope' => env('EID_BUYPASS_SCOPE', 'openid profile email'),
        ],
    ],
];
