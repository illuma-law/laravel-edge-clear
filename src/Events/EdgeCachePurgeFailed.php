<?php

declare(strict_types=1);

namespace IllumaLaw\EdgeClear\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Throwable;

final class EdgeCachePurgeFailed
{
    use Dispatchable;

    public function __construct(
        public readonly string $reason,
        public readonly Throwable $exception,
    ) {}
}
