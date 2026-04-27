<?php

declare(strict_types=1);

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
    'api_key'   => env('CLOUDFLARE_API_KEY'),

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

    /*
     * The default TTL for cache headers.
     */
    'default_cache_ttl' => (int) env('EDGE_CLEAR_DEFAULT_TTL', 600),

    /*
     * The fallback TTL when permanent cache is opted out.
     */
    'short_fallback_ttl' => (int) env('EDGE_CLEAR_FALLBACK_TTL', 120),

    /*
     * Determine if permanent edge caching is enabled.
     */
    'permanent_edge_cache_enabled' => (bool) env('EDGE_CLEAR_PERMANENT_ENABLED', true),

    /*
     * The s-maxage for permanent edge caching (default 1 year).
     */
    'permanent_s_maxage' => (int) env('EDGE_CLEAR_PERMANENT_S_MAXAGE', 31536000),

    /*
     * Default tags to apply to all cached responses.
     */
    'default_tags' => [],

    /*
     * The mode for purging: 'tags', 'urls', or 'auto'.
     */
    'purge_mode' => env('EDGE_CLEAR_PURGE_MODE', 'urls'),

    /*
     * The TTL for unique job locking.
     */
    'purge_unique_ttl' => (int) env('EDGE_CLEAR_PURGE_UNIQUE_TTL', 45),

    /*
     * HTTP status codes that trigger a fallback from tag to URL purging.
     */
    'auto_purge_fallback_codes' => [400, 403],
];
