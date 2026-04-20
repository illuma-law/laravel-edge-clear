<?php

return [
    /*
     * The Cloudflare Zone ID to purge.
     */
    'zone_id' => env('CLOUDFLARE_ZONE_ID'),

    /*
     * The Cloudflare API Token (recommended).
     */
    'api_token' => env('CLOUDFLARE_API_TOKEN'),

    /*
     * Legacy API authentication.
     */
    'api_email' => env('CLOUDFLARE_API_EMAIL'),
    'api_key' => env('CLOUDFLARE_API_KEY'),

    /*
     * Determine if purging is enabled.
     */
    'enabled' => env('EDGE_CLEAR_ENABLED', true),

    /*
     * When set to true, purging only happens in production environment.
     */
    'only_in_production' => env('EDGE_CLEAR_ONLY_IN_PRODUCTION', true),

    /*
     * Enable debug mode to log requests.
     */
    'debug' => env('EDGE_CLEAR_DEBUG', false),
];
