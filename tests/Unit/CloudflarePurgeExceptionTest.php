<?php

declare(strict_types=1);

use IllumaLaw\EdgeClear\Exceptions\CloudflarePurgeException;

it('formats message with status code and message', function (): void {
    $exception = CloudflarePurgeException::requestError(403, 'Forbidden');

    expect($exception->getMessage())->toBe('Cloudflare request failed with status 403: Forbidden');
    expect($exception->getCode())->toBe(403);
});

it('includes error code in message when provided as integer', function (): void {
    $exception = CloudflarePurgeException::requestError(400, 'Bad request', 1001);

    expect($exception->getMessage())->toBe('Cloudflare request failed with status 400: Bad request (code: 1001)');
    expect($exception->getCode())->toBe(400);
});

it('includes error code in message when provided as string', function (): void {
    $exception = CloudflarePurgeException::requestError(422, 'Unprocessable', 'CF-ERR-42');

    expect($exception->getMessage())->toBe('Cloudflare request failed with status 422: Unprocessable (code: CF-ERR-42)');
    expect($exception->getCode())->toBe(422);
});

it('omits error code suffix when error code is null', function (): void {
    $exception = CloudflarePurgeException::requestError(500, 'Internal Server Error', null);

    expect($exception->getMessage())->toBe('Cloudflare request failed with status 500: Internal Server Error');
    expect($exception->getMessage())->not->toContain('code:');
});

it('is an instance of RuntimeException', function (): void {
    $exception = CloudflarePurgeException::requestError(404, 'Not found');

    expect($exception)->toBeInstanceOf(RuntimeException::class);
});
