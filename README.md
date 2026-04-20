# Laravel Edge Clear

[![Tests](https://github.com/illuma-law/laravel-edge-clear/actions/workflows/tests.yml/badge.svg)](https://github.com/illuma-law/laravel-edge-clear/actions/workflows/tests.yml)
[![PHPStan](https://github.com/illuma-law/laravel-edge-clear/actions/workflows/phpstan.yml/badge.svg)](https://github.com/illuma-law/laravel-edge-clear/actions/workflows/phpstan.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/illuma-law/laravel-edge-clear.svg)](https://packagist.org/packages/illuma-law/laravel-edge-clear)
[![License](https://img.shields.io/packagist/l/illuma-law/laravel-edge-clear.svg)](LICENSE.md)

A standalone Cloudflare cache-purging package for Laravel 11, 12, and 13. Provides a fluent service, a Facade, and a middleware to bypass edge caches per request.

---

## Installation

Install via Composer:

```bash
composer require illuma-law/laravel-edge-clear
```

The service provider is auto-discovered by Laravel. To publish the configuration file:

```bash
php artisan vendor:publish --tag=edge-clear-config
```

---

## Configuration

After publishing, the config is available at `config/edge-clear.php`:

```php
return [
    // The Cloudflare Zone ID to purge.
    'zone_id' => env('CLOUDFLARE_ZONE_ID'),

    // API Token authentication (recommended).
    'api_token' => env('CLOUDFLARE_API_TOKEN'),

    // Legacy Global API Key authentication.
    'api_email' => env('CLOUDFLARE_API_EMAIL'),
    'api_key'   => env('CLOUDFLARE_API_KEY'),

    // Master switch — set to false to disable all purging.
    'enabled' => env('EDGE_CLEAR_ENABLED', true),

    // When true, purging only runs in the production environment.
    'only_in_production' => env('EDGE_CLEAR_ONLY_IN_PRODUCTION', true),

    // Log all request/response payloads to the default log channel.
    'debug' => env('EDGE_CLEAR_DEBUG', false),
];
```

### Environment Variables

Add these to your `.env` file:

```env
CLOUDFLARE_ZONE_ID=your-zone-id
CLOUDFLARE_API_TOKEN=your-api-token

# Optional: override production-only guard during development
EDGE_CLEAR_ONLY_IN_PRODUCTION=false
```

---

## Usage

### Facade

```php
use IllumaLaw\EdgeClear\Facades\CloudflarePurger;

// Purge specific URLs
CloudflarePurger::purgeByUrls([
    'https://example.com/page',
    'https://example.com/other-page',
]);

// Purge by cache tags (Enterprise plan required)
CloudflarePurger::purgeByTags(['tag:blog', 'tag:news']);

// Purge the entire zone cache
CloudflarePurger::purgeEverything();

// Check if purging is currently active
if (CloudflarePurger::isActive()) {
    // ...
}
```

### Dependency Injection

```php
use IllumaLaw\EdgeClear\CloudflarePurger;

class ArticleController
{
    public function __construct(
        private readonly CloudflarePurger $purger
    ) {}

    public function update(Article $article): void
    {
        // ... update logic ...

        $this->purger->purgeByUrls([route('articles.show', $article)]);
    }
}
```

### Service Container

```php
$purger = app('cloudflare-purger');
$purger->purgeEverything();
```

### Bypass Edge Cache Middleware

Register the middleware in your `bootstrap/app.php` or routes to prevent Cloudflare from caching specific responses:

```php
use IllumaLaw\EdgeClear\Middleware\BypassEdgeCache;

// In bootstrap/app.php (Laravel 11+)
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias(['bypass-edge-cache' => BypassEdgeCache::class]);
})
```

Then apply to routes:

```php
Route::get('/admin/dashboard', DashboardController::class)
    ->middleware('bypass-edge-cache');
```

The middleware sets `Cache-Control: no-cache, no-store, must-revalidate`, `Pragma: no-cache`, and `Expires: 0` on every matching response.

### Error Handling

All HTTP failures throw a typed `CloudflarePurgeException`:

```php
use IllumaLaw\EdgeClear\Exceptions\CloudflarePurgeException;
use IllumaLaw\EdgeClear\Facades\CloudflarePurger;

try {
    CloudflarePurger::purgeEverything();
} catch (CloudflarePurgeException $e) {
    // $e->getMessage() contains status code, Cloudflare message, and error code
    // $e->getCode()    contains the HTTP status code
    Log::error('Cache purge failed', ['error' => $e->getMessage()]);
}
```

### Return Values

| Method            | Returns                                   |
|-------------------|-------------------------------------------|
| `purgeByUrls()`   | `string` (result ID) or `true` or `false` |
| `purgeByTags()`   | `string` (result ID) or `true` or `false` |
| `purgeEverything()` | `bool`                                  |

A return value of `false` indicates either the purger is inactive (disabled or wrong environment) or Cloudflare returned `success: false` without an HTTP error.

---

## Testing

```bash
composer install
vendor/bin/pest
```

To check code coverage:

```bash
vendor/bin/pest --coverage --min=100
```

To run static analysis:

```bash
vendor/bin/phpstan analyse --configuration=phpstan.neon
```

To check code style:

```bash
vendor/bin/pint --test
```

---

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for recent changes.

## Security

If you discover any security-related issues, please email [hello@illuma.law](mailto:hello@illuma.law) instead of using the issue tracker.

## Credits

- [Illuma Law](https://illuma.law)
- [All Contributors](https://github.com/illuma-law/laravel-edge-clear/contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
