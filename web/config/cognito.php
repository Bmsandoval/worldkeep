<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cognito Hosted UI (stardate PR #39 / go-cognito-hosted-starter)
    |--------------------------------------------------------------------------
    */

    'region' => env('COGNITO_REGION', 'us-east-1'),

    'user_pool_id' => env('COGNITO_USER_POOL_ID'),

    'app_client_id' => env('COGNITO_APP_CLIENT_ID'),

    'app_client_secret' => env('COGNITO_APP_CLIENT_SECRET'),

    /** Hostname only (auth.example.com) or full Cognito domain */
    'domain' => env('COGNITO_DOMAIN'),

    /** Must match Cognito app client callback (e.g. http://127.0.0.1:8000/app/auth/redirect) */
    'redirect_uri' => env('COGNITO_REDIRECT_URI', 'http://127.0.0.1:8000/app/auth/redirect'),

    /** CLI / desktop localhost OAuth callback (go-bash-shell-cli-starter default) */
    'cli_redirect_uri' => env('COGNITO_CLI_REDIRECT_URI', 'http://127.0.0.1:53682/callback'),

    /** Additional Cognito callback URLs (mobile deep links, desktop, SPA, etc.) */
    'allowed_redirect_uris' => array_values(array_filter([
        env('COGNITO_REDIRECT_URI', 'http://127.0.0.1:8000/app/auth/redirect'),
        env('COGNITO_CLI_REDIRECT_URI', 'http://127.0.0.1:53682/callback'),
        env('COGNITO_MOBILE_REDIRECT_URI', 'myapp://auth/redirect'),
        env('COGNITO_DESKTOP_REDIRECT_URI', 'myapp://auth/callback'),
    ])),

    'logout_uri' => env('COGNITO_LOGOUT_URI', 'http://127.0.0.1:8000/app'),

];
