<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Campaign defaults
    |--------------------------------------------------------------------------
    */
    'campaign_id' => env('WORLDKEEP_CAMPAIGN_ID', 'campaign_001'),

    'role' => env('WORLDKEEP_ROLE', 'owner'),

    'api_token' => env('WORLDKEEP_API_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | MCP OAuth (ChatGPT connector — RFC 9728, in-process Laravel /mcp)
    |--------------------------------------------------------------------------
    */
    'mcp' => [
        'public_url' => env('WORLDKEEP_MCP_PUBLIC_URL'),
        'cognito_issuer' => env('WORLDKEEP_COGNITO_ISSUER', env('COGNITO_ISSUER')),
        'oauth_scopes' => ['openid', 'email'],
    ],

    'timeout_seconds' => (int) env('WORLDKEEP_HTTP_TIMEOUT', 15),

    'srd_version' => env('WORLDKEEP_SRD_VERSION', 'srd-2014'),

    'entity_types' => ['npc', 'location', 'faction', 'plot'],

    'open5e' => [
        'base_url' => env('WORLDKEEP_OPEN5E_BASE_URL', 'https://api.open5e.com/v2'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Legacy internal URL (unused when Engine runs in-process)
    |--------------------------------------------------------------------------
    */
    'internal_url' => rtrim(env('WORLDKEEP_INTERNAL_URL', 'http://127.0.0.1:8788'), '/'),
];
