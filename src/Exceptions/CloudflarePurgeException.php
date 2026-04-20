<?php

declare(strict_types=1);

namespace IllumaLaw\EdgeClear\Exceptions;

use RuntimeException;

final class CloudflarePurgeException extends RuntimeException
{
    public static function requestError(int $statusCode, string $message, int|string|null $errorCode = null): self
    {
        $formattedMessage = sprintf(
            'Cloudflare request failed with status %d: %s',
            $statusCode,
            $message
        );

        if ($errorCode !== null) {
            $formattedMessage .= sprintf(' (code: %s)', (string) $errorCode);
        }

        return new self($formattedMessage, $statusCode);
    }
}
