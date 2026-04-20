<?php

declare(strict_types=1);

namespace IllumaLaw\EdgeClear\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetEdgeCacheHeaders
{
    protected const TAGS_ATTR = 'edge-clear-tags';

    protected const TTL_ATTR = 'edge-clear-ttl';

    public function handle(Request $request, Closure $next, string $ttl = '', string $tags = '', string $cacheProfile = 'short'): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if (! $this->shouldCacheResponse($request, $response)) {
            return $response;
        }

        $resolvedTtl = $this->resolveTtl($request, $ttl, $cacheProfile);

        $this->applyCacheControlHeader($response, $resolvedTtl, $cacheProfile);
        $this->applyCacheTagsHeader($request, $response, $tags);

        $request->attributes->set(self::TTL_ATTR, $resolvedTtl);
        $response->headers->remove('set-cookie');

        return $response;
    }

    protected function shouldCacheResponse(Request $request, Response $response): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        if (! $response->isSuccessful()) {
            return false;
        }

        return true;
    }

    protected function resolveTtl(Request $request, string $ttl, string $cacheProfile): int
    {
        if ($ttl !== '') {
            return max(1, (int) $ttl);
        }

        $defaultTtl = config('edge-clear.default_cache_ttl', 600);
        $defaultTtl = is_numeric($defaultTtl) ? (int) $defaultTtl : 600;

        $attrTtl = $request->attributes->get(self::TTL_ATTR, $defaultTtl);

        return max(1, is_numeric($attrTtl) ? (int) $attrTtl : 600);
    }

    protected function applyCacheControlHeader(Response $response, int $resolvedTtl, string $cacheProfile): void
    {
        $permanentEnabled = config('edge-clear.permanent_edge_cache_enabled', true);
        if ($cacheProfile === 'permanent' && (bool) $permanentEnabled) {
            $sMaxAgeConfig = config('edge-clear.permanent_s_maxage', 31_536_000);
            $sMaxAge = is_numeric($sMaxAgeConfig) ? (int) $sMaxAgeConfig : 31_536_000;
            if ($sMaxAge < 1) {
                $sMaxAge = 31_536_000;
            }
            $response->headers->set('Cache-Control', "max-age=0, public, s-maxage={$sMaxAge}");
        } else {
            $response->headers->set('Cache-Control', "max-age={$resolvedTtl}, public");
        }
    }

    protected function applyCacheTagsHeader(Request $request, Response $response, string $tags): void
    {
        $resolvedTags = $this->resolveTags($request, $tags);
        if ($resolvedTags !== []) {
            $response->headers->set('Cache-Tag', implode(',', array_map(
                static fn (string $t): string => static::sanitizeCacheTag($t),
                $resolvedTags
            )));
        }
    }

    /**
     * @return array<int, string>
     */
    protected function resolveTags(Request $request, string $tags): array
    {
        $routeTags = $tags !== '' ? explode(';', $tags) : [];

        $defaultTags = config('edge-clear.default_tags', []);
        if (! is_array($defaultTags)) {
            $defaultTags = [];
        }

        $attrTags = $request->attributes->get(self::TAGS_ATTR, []);
        if (! is_array($attrTags)) {
            $attrTags = [];
        }

        $allTags = collect($defaultTags)
            ->merge($attrTags)
            ->merge($routeTags);

        $normalizedTags = $allTags
            ->map(static fn (mixed $tag): string => is_scalar($tag) ? trim((string) $tag) : '')
            ->filter(static fn (string $tag): bool => $tag !== '')
            ->unique()
            ->values()
            ->all();

        $request->attributes->set(self::TAGS_ATTR, $normalizedTags);

        return $normalizedTags;
    }

    public static function sanitizeCacheTag(string $tag): string
    {
        $ascii = (string) preg_replace('/[^[:print:]]/', '', $tag);
        $ascii = str_replace([',', ' '], '-', $ascii);

        return $ascii !== '' ? $ascii : 'edge';
    }
}
