<?php

declare(strict_types=1);

namespace IllumaLaw\EdgeClear\Jobs;

use IllumaLaw\EdgeClear\CloudflarePurger;
use IllumaLaw\EdgeClear\Events\EdgeCachePurged;
use IllumaLaw\EdgeClear\Events\EdgeCachePurgeFailed;
use IllumaLaw\EdgeClear\Exceptions\CloudflarePurgeException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class PurgeEdgeCacheJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    /**
     * @var list<int>
     */
    public array $backoff = [15, 45, 120, 300];

    /**
     * @param  array<int, string>  $tags
     * @param  array<int, string>  $urls
     */
    public function __construct(
        public readonly array $tags,
        public readonly array $urls,
        public readonly string $reason,
    ) {}

    public function uniqueId(): string
    {
        $tags = $this->tags;
        $urls = $this->urls;
        sort($tags, SORT_STRING);
        sort($urls, SORT_STRING);

        $payload = [
            'tags'   => $tags,
            'urls'   => $urls,
            'reason' => $this->reason,
        ];

        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE));
    }

    public function uniqueFor(): int
    {
        $ttl = config('edge-clear.purge_unique_ttl', 45);
        $ttl = is_numeric($ttl) ? (int) $ttl : 45;

        return max(1, $ttl);
    }

    public function handle(CloudflarePurger $cache): void
    {
        if (! $cache->isActive()) {
            Log::info('cloudflare.purge.skipped_inactive', [
                'reason' => $this->reason,
            ]);

            return;
        }

        $mode = config('edge-clear.purge_mode', 'urls');
        $mode = is_string($mode) ? strtolower(trim($mode)) : 'urls';

        Log::info('cloudflare.purge.start', [
            'reason'     => $this->reason,
            'purge_mode' => $mode,
            'tag_count'  => count($this->tags),
            'url_count'  => count($this->urls),
        ]);

        try {
            $handled = false;
            $lastPurgeId = null;

            if ($mode === 'auto' && $this->tags !== []) {
                try {
                    $lastPurgeId = $cache->purgeByTags($this->tags);
                    $handled = true;
                } catch (CloudflarePurgeException $e) {
                    if ($this->urls !== [] && $this->shouldAutoFallbackFromTagFailure($e)) {
                        Log::warning('cloudflare.purge.auto_tag_fallback_to_urls', [
                            'reason'    => $this->reason,
                            'exception' => $e::class,
                            'message'   => $e->getMessage(),
                            'status'    => $e->getCode(),
                        ]);
                        foreach (array_chunk($this->urls, 25) as $chunk) {
                            $lastPurgeId = $cache->purgeByUrls($chunk);
                        }
                        $handled = true;
                    } else {
                        throw $e;
                    }
                }
            }

            if (! $handled && $mode === 'tags' && $this->tags !== []) {
                $lastPurgeId = $cache->purgeByTags($this->tags);
                $handled = true;
            }

            if (! $handled && $this->urls !== []) {
                foreach (array_chunk($this->urls, 25) as $chunk) {
                    $lastPurgeId = $cache->purgeByUrls($chunk);
                }
                $handled = true;
            }

            if (! $handled && $this->tags !== []) {
                $lastPurgeId = $cache->purgeByTags($this->tags);
                $handled = true;
            }

            if ($handled) {
                $purgeIdString = is_string($lastPurgeId) ? $lastPurgeId : null;
                $this->recordSuccess($purgeIdString);
                EdgeCachePurged::dispatch($this->reason, $purgeIdString);
            }

            if (! $handled) {
                Log::warning('cloudflare.purge.empty_scope', ['reason' => $this->reason]);
            }
        } catch (Throwable $e) {
            $this->recordFailure($e);
            EdgeCachePurgeFailed::dispatch($this->reason, $e);
            throw $e;
        }
    }

    protected function shouldAutoFallbackFromTagFailure(CloudflarePurgeException $e): bool
    {
        $status = $e->getCode();
        $codes = config('edge-clear.auto_purge_fallback_codes', [400, 403]);
        if (! is_array($codes)) {
            $codes = [400, 403];
        }

        $codes = array_map(static fn (mixed $c): int => is_numeric($c) ? (int) $c : 0, $codes);

        if (in_array($status, $codes, true)) {
            return true;
        }

        $message = strtolower($e->getMessage());

        foreach ([
            'tag',
            'tags',
            'enterprise',
            'not entitled',
            'not available',
            'forbidden',
            'unauthorized',
            '10015',
        ] as $needle) {
            if (str_contains($message, $needle)) {
                return true;
            }
        }

        return false;
    }

    protected function recordSuccess(?string $purgeId): void
    {
        Log::info('cloudflare.purge.success', [
            'reason'              => $this->reason,
            'cloudflare_purge_id' => $purgeId,
        ]);
    }

    protected function recordFailure(Throwable $e): void
    {
        Log::error('cloudflare.purge.failed', [
            'reason'    => $this->reason,
            'exception' => $e::class,
            'message'   => $e->getMessage(),
        ]);
    }
}
