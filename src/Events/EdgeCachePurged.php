<?php

declare(strict_types=1);

namespace IllumaLaw\EdgeClear\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class EdgeCachePurged
{
    use Dispatchable;

    public function __construct(
        public readonly string $reason,
        public readonly ?string $purgeId = null,
    ) {}
}
