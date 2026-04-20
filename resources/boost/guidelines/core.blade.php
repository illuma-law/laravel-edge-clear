# illuma-law/laravel-edge-clear

Cloudflare cache purging and cache bypass management for Laravel.

## Usage

### Purging Cache
Use the `CloudflarePurger` facade:

```php
use IllumaLaw\EdgeClear\Facades\CloudflarePurger;

// Purge specific URLs
CloudflarePurger::purgeByUrls(['https://example.com/page']);

// Purge by tags (Enterprise only)
CloudflarePurger::purgeByTags(['tag:blog']);

// Purge everything
CloudflarePurger::purgeEverything();
```

### Cache Bypass Middleware
Prevents Cloudflare from caching specific routes (e.g., admin, checkout).

```php
Route::get('/admin', ...)->middleware(\IllumaLaw\EdgeClear\Middleware\BypassEdgeCache::class);
```

## Configuration

Publish config: `php artisan vendor:publish --tag="edge-clear-config"`

Required `.env` values:
- `CLOUDFLARE_ZONE_ID`
- `CLOUDFLARE_API_TOKEN` (recommended) or `CLOUDFLARE_API_EMAIL` + `CLOUDFLARE_API_KEY`

Options in `config/edge-clear.php`:
- `enabled`: Global toggle.
- `only_in_production`: Prevent API calls in local development.
