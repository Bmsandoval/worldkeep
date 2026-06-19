<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Internal Go engine (MCP + REST on one process)
    |--------------------------------------------------------------------------
    |
    | PHP UI calls this base URL server-side. In the unified Docker image,
    | Go listens on 127.0.0.1:8788 while Apache serves Laravel on :80.
    |
    */
    'internal_url' => rtrim(env('WORLDKEEP_INTERNAL_URL', 'http://127.0.0.1:8788'), '/'),

    'campaign_id' => env('WORLDKEEP_CAMPAIGN_ID', 'campaign_001'),

    'api_token' => env('WORLDKEEP_API_TOKEN'),

    'timeout_seconds' => (int) env('WORLDKEEP_HTTP_TIMEOUT', 15),

    'entity_types' => ['npc', 'location', 'faction', 'plot'],
];
