---
description: Cloudflare CDN cache purging and cache bypass middleware for Laravel
---

# laravel-edge-clear

Standalone Cloudflare cache purging and cache bypass management. Provides a fluent service, facade, and middleware for managing CDN cache from Laravel.

## Namespace

`IllumaLaw\EdgeClear`

## Key Classes & Events

- `EdgeClear` facade — purge by URL, tag, or everything
- `PurgeEdgeCacheJob` — queued job with tag→URL fallback
- `EdgeCachePurged` event — fired on success
- `EdgeCachePurgeFailed` event — fired on failure

## Usage

```php
use IllumaLaw\EdgeClear\Facades\EdgeClear;

// Purge specific URLs
EdgeClear::purgeUrls(['https://example.com/page', 'https://example.com/other']);

// Purge by cache tags (Cloudflare Enterprise)
EdgeClear::purgeTags(['articles', 'homepage']);

// Purge everything
EdgeClear::purgeAll();
```

## Queued Purge (preferred in production)

```php
use IllumaLaw\EdgeClear\Jobs\PurgeEdgeCacheJob;

PurgeEdgeCacheJob::dispatch(
    tags: ['articles'],
    urls: ['https://example.com/articles'],
);
// Automatically falls back from tag-purge to URL-purge if Enterprise features unavailable
```

## Middleware

```php
// routes/web.php
Route::get('/articles', ArticlesController::class)
    ->middleware(\IllumaLaw\EdgeClear\Middleware\SetCacheHeaders::class);

Route::post('/admin/articles', StoreArticle::class)
    ->middleware(\IllumaLaw\EdgeClear\Middleware\BypassEdgeCache::class);
```

## Config

Publish: `php artisan vendor:publish --tag="edge-clear-config"`

```env
CLOUDFLARE_API_TOKEN=your_token
CLOUDFLARE_ZONE_ID=your_zone_id
# Or use email+key combo:
CLOUDFLARE_API_EMAIL=
CLOUDFLARE_API_KEY=
```

## Registration (AppServiceProvider)

Listen to lifecycle events to keep application state in sync:
```php
Event::listen(EdgeCachePurged::class, fn ($e) => Log::info('purged', $e->urls));
Event::listen(EdgeCachePurgeFailed::class, fn ($e) => report($e->exception));
```
