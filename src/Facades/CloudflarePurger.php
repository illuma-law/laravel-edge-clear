<?php

declare(strict_types=1);

namespace IllumaLaw\EdgeClear\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool|string purgeByUrls(array<int, string> $urls)
 * @method static bool|string purgeByTags(array<int, string> $tags)
 * @method static bool purgeEverything()
 * @method static bool isActive()
 *
 * @see \IllumaLaw\EdgeClear\CloudflarePurger
 */
final class CloudflarePurger extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'cloudflare-purger';
    }
}
