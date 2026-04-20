# Laravel Edge Clear

[![Tests](https://github.com/illuma-law/laravel-edge-clear/actions/workflows/run-tests.yml/badge.svg)](https://github.com/illuma-law/laravel-edge-clear/actions)
[![Packagist License](https://img.shields.io/badge/Licence-MIT-blue)](http://choosealicense.com/licenses/mit/)
[![Latest Stable Version](https://img.shields.io/packagist/v/illuma-law/laravel-edge-clear?label=Version)](https://packagist.org/packages/illuma-law/laravel-edge-clear)

**Standalone Cloudflare cache purging for Laravel**

This package provides a fluent service, a Facade, and a middleware to bypass edge caches per request.

- [Installation](#installation)
- [Usage](#usage)
  - [Basic Usage](#basic-usage)
  - [Middleware](#middleware)
  - [Error Handling](#error-handling)
- [Testing](#testing)
- [Credits](#credits)
- [License](#license)

## Installation

You can install the package via composer:

```bash
composer require illuma-law/laravel-edge-clear
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="edge-clear-config"
```

## Usage

### TL;DR

```php
use IllumaLaw\EdgeClear\Facades\CloudflarePurger;

CloudflarePurger::purgeEverything();
```

### Basic Usage

Register the Cloudflare credentials in your `.env` file:

```env
CLOUDFLARE_ZONE_ID=your-zone-id
CLOUDFLARE_API_TOKEN=your-api-token
```

Then use the facade to purge specific URLs or tags:

```php
use IllumaLaw\EdgeClear\Facades\CloudflarePurger;

// Purge specific URLs
CloudflarePurger::purgeByUrls([
    'https://example.com/page',
]);

// Purge by cache tags (Enterprise plan required)
CloudflarePurger::purgeByTags(['tag:blog']);

// Purge the entire zone cache
CloudflarePurger::purgeEverything();
```

### Middleware

The `BypassEdgeCache` middleware sets headers to prevent Cloudflare from caching specific responses.

```php
use IllumaLaw\EdgeClear\Middleware\BypassEdgeCache;

Route::get('/admin/dashboard', DashboardController::class)
    ->middleware(BypassEdgeCache::class);
```

### Error Handling

All HTTP failures throw a typed `CloudflarePurgeException`.

```php
use IllumaLaw\EdgeClear\Exceptions\CloudflarePurgeException;

try {
    CloudflarePurger::purgeEverything();
} catch (CloudflarePurgeException $e) {
    Log::error('Cache purge failed', ['error' => $e->getMessage()]);
}
```

## Testing

The package includes a comprehensive test suite using Pest.

```bash
composer test
```

## Credits

- [illuma-law](https://github.com/illuma-law)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
