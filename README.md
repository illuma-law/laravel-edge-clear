# Laravel Edge Clear

[![Tests](https://github.com/illuma-law/laravel-edge-clear/actions/workflows/run-tests.yml/badge.svg)](https://github.com/illuma-law/laravel-edge-clear/actions)
[![Packagist License](https://img.shields.io/badge/Licence-MIT-blue)](http://choosealicense.com/licenses/mit/)
[![Latest Stable Version](https://img.shields.io/packagist/v/illuma-law/laravel-edge-clear?label=Version)](https://packagist.org/packages/illuma-law/laravel-edge-clear)

Standalone Cloudflare cache purging and cache bypass management for Laravel applications.

This package provides a fluent service, a Facade, and specialized middleware designed to help you manage your Cloudflare CDN cache directly from your Laravel application.

## Features

- **Purge Specific URLs:** Invalidate only the pages that have changed.
- **Purge by Tags:** Quickly wipe out categories of content across your site using Cloudflare Cache Tags (requires Cloudflare Enterprise).
- **Purge Everything:** The "nuke from orbit" option.
- **Generic Purge Job:** A robust `PurgeEdgeCacheJob` that handles automatic fallback from tag-based purging to URL-based purging if Enterprise features are missing.
- **Set Cache Headers Middleware:** Easily manage `Cache-Control` and `Cache-Tag` headers for your routes.
- **Cache Bypass Middleware:** Attach middleware to specific routes or controllers to ensure Cloudflare never caches them.
- **Lifecycle Events:** Listen to `EdgeCachePurged` and `EdgeCachePurgeFailed` to keep your application state in sync with the CDN.
- **Environment Awareness:** Safely test your code locally without accidentally triggering real API calls.


## Installation

You can install the package via composer:

```bash
composer require illuma-law/laravel-edge-clear
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="edge-clear-config"
```

## Configuration

In your `.env` file, configure your Cloudflare credentials. You can use either an API Token (recommended) or an API Email + Key combo.

```env
CLOUDFLARE_ZONE_ID="your-zone-id"
CLOUDFLARE_API_TOKEN="your-api-token"
```

The published `config/edge-clear.php` config looks like this:

```php
return [
    'zone_id' => env('CLOUDFLARE_ZONE_ID'),
    
    // Auth method 1 (Preferred)
    'api_token' => env('CLOUDFLARE_API_TOKEN'),
    
    // Auth method 2
    'api_email' => env('CLOUDFLARE_API_EMAIL'),
    'api_key' => env('CLOUDFLARE_API_KEY'),

    // Toggle the service globally
    'enabled' => env('CLOUDFLARE_ENABLED', true),

    // Prevent accidental API calls in local dev
    'only_in_production' => env('CLOUDFLARE_ONLY_IN_PRODUCTION', true),

    // Enable detailed API request logging
    'debug' => env('CLOUDFLARE_DEBUG', false),
];
```

## Usage & Integration

### Triggering Purges

Use the `CloudflarePurger` facade to interact with the cache:

```php
use IllumaLaw\EdgeClear\Facades\CloudflarePurger;

// Purge specific URLs (Useful in Model Observers)
CloudflarePurger::purgeByUrls([
    'https://example.com/blog/my-updated-post',
    'https://example.com/blog',
]);

// Purge by cache tags (Requires Cloudflare Enterprise)
CloudflarePurger::purgeByTags(['tag:blog', 'tag:news']);

// Purge the entire zone cache (Useful after deploying huge site changes)
CloudflarePurger::purgeEverything();
```

### Real-world Example: Eloquent Observer

Invalidate your cache automatically when a model is saved or deleted:

```php
namespace App\Observers;

use App\Models\Post;
use IllumaLaw\EdgeClear\Facades\CloudflarePurger;

class PostObserver
{
    public function saved(Post $post): void
    {
        // Purge the specific post and the blog index
        CloudflarePurger::purgeByUrls([
            url("/blog/{$post->slug}"),
            url('/blog'),
        ]);
    }
}
```

### Route Middleware

If you have specific routes that should never be cached by Cloudflare (like admin dashboards, carts, or user profiles), you can use the `BypassEdgeCache` middleware. It automatically appends headers (`Cache-Control: no-store, no-cache...`) that instruct Cloudflare to skip caching the response.

```php
use IllumaLaw\EdgeClear\Middleware\BypassEdgeCache;

Route::get('/admin/dashboard', [DashboardController::class, 'index'])
    ->middleware(BypassEdgeCache::class);
```

### Set Cache Headers

Use the `SetEdgeCacheHeaders` middleware to manage your CDN headers fluently. It handles `Cache-Control` (including `s-maxage`) and `Cache-Tag` injection.

```php
use IllumaLaw\EdgeClear\Middleware\SetEdgeCacheHeaders;

Route::get('/public-news', [NewsController::class, 'index'])
    ->middleware(SetEdgeCacheHeaders::class.':600;tag:news;short');
```

The middleware parameters are: `ttl`, `tags` (separated by `;`), and `profile` (`short` or `permanent`).

### Queued Purging (with Fallback)

For high-volume sites, you can use the `PurgeEdgeCacheJob`. It automatically detects if your Cloudflare plan supports Cache Tags and falls back to URL purging if needed.

```php
use IllumaLaw\EdgeClear\Jobs\PurgeEdgeCacheJob;

PurgeEdgeCacheJob::dispatch(
    tags: ['tag:blog'],
    urls: ['https://example.com/blog'],
    reason: 'blog_updated'
);
```

### Lifecycle Events

The package dispatches events that you can listen to for logging or updating your application state:

- `IllumaLaw\EdgeClear\Events\EdgeCachePurged`: Dispatched when a purge request is successful.
- `IllumaLaw\EdgeClear\Events\EdgeCachePurgeFailed`: Dispatched when a purge request fails after all retries.

### Error Handling


If Cloudflare rejects your request (e.g., bad token, invalid URLs), the package will throw a typed `CloudflarePurgeException`.

```php
use IllumaLaw\EdgeClear\Exceptions\CloudflarePurgeException;
use IllumaLaw\EdgeClear\Facades\CloudflarePurger;

try {
    CloudflarePurger::purgeEverything();
} catch (CloudflarePurgeException $e) {
    Log::error('Cache purge failed', [
        'message' => $e->getMessage(),
        'http_status' => $e->getHttpStatus(),
        'cf_error_code' => $e->getCloudflareErrorCode(),
    ]);
}
```

## Testing

The package includes a comprehensive test suite using Pest.

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
