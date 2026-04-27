<?php

declare(strict_types=1);

use IllumaLaw\EdgeClear\CloudflarePurger;
use IllumaLaw\EdgeClear\Exceptions\CloudflarePurgeException;
use IllumaLaw\EdgeClear\Facades\CloudflarePurger as CloudflarePurgerFacade;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function (): void {
    $this->config = [
        'zone_id'            => 'test-zone-id',
        'api_token'          => 'test-api-token',
        'api_email'          => null,
        'api_key'            => null,
        'enabled'            => true,
        'only_in_production' => false,
        'debug'              => false,
    ];

    config(['edge-clear' => $this->config]);

    $this->purger = new CloudflarePurger($this->config, 'production');
});

it('is active when fully configured with api_token', function (): void {
    expect($this->purger->isActive())->toBeTrue();
});

it('is active when configured with email and key auth', function (): void {
    $purger = new CloudflarePurger(array_merge($this->config, [
        'api_token' => null,
        'api_email' => 'test@example.com',
        'api_key'   => 'test-key',
    ]), 'production');

    expect($purger->isActive())->toBeTrue();
});

it('is inactive when enabled is false', function (): void {
    $purger = new CloudflarePurger(array_merge($this->config, ['enabled' => false]), 'production');

    expect($purger->isActive())->toBeFalse();
});

it('is inactive when only_in_production and not in production', function (): void {
    $purger = new CloudflarePurger(array_merge($this->config, ['only_in_production' => true]), 'local');

    expect($purger->isActive())->toBeFalse();
});

it('is active when only_in_production and in production environment', function (): void {
    $purger = new CloudflarePurger(array_merge($this->config, ['only_in_production' => true]), 'production');

    expect($purger->isActive())->toBeTrue();
});

it('is inactive when zone_id is null', function (): void {
    $purger = new CloudflarePurger(array_merge($this->config, ['zone_id' => null]), 'production');

    expect($purger->isActive())->toBeFalse();
});

it('is inactive when api_token is null and no email auth configured', function (): void {
    $purger = new CloudflarePurger(array_merge($this->config, [
        'api_token' => null,
        'api_email' => null,
        'api_key'   => null,
    ]), 'production');

    expect($purger->isActive())->toBeFalse();
});

it('is inactive when api_token is null and only api_email is provided without api_key', function (): void {
    $purger = new CloudflarePurger(array_merge($this->config, [
        'api_token' => null,
        'api_email' => 'test@example.com',
        'api_key'   => null,
    ]), 'production');

    expect($purger->isActive())->toBeFalse();
});

it('purges by urls and returns result id', function (): void {
    Http::fake([
        'api.cloudflare.com/*' => Http::response(['success' => true, 'result' => ['id' => 'purge-id']], 200),
    ]);

    $result = $this->purger->purgeByUrls(['https://example.com/page1', 'https://example.com/page2']);

    expect($result)->toBe('purge-id');

    Http::assertSent(function (Request $request): bool {
        return $request->url() === 'https://api.cloudflare.com/client/v4/zones/test-zone-id/purge_cache'
            && $request['files'] === ['https://example.com/page1', 'https://example.com/page2'];
    });
});

it('purges by urls returns true when no result id in response', function (): void {
    Http::fake([
        'api.cloudflare.com/*' => Http::response(['success' => true, 'result' => []], 200),
    ]);

    $result = $this->purger->purgeByUrls(['https://example.com/page1']);

    expect($result)->toBeTrue();
});

it('returns false from purgeByUrls when purger is inactive', function (): void {
    $purger = new CloudflarePurger(array_merge($this->config, ['enabled' => false]), 'production');

    $result = $purger->purgeByUrls(['https://example.com/page1']);

    expect($result)->toBeFalse();
    Http::assertNothingSent();
});

it('purges by tags and returns result id', function (): void {
    Http::fake([
        'api.cloudflare.com/*' => Http::response(['success' => true, 'result' => ['id' => 'tag-purge-id']], 200),
    ]);

    $result = $this->purger->purgeByTags(['tag1', 'tag2']);

    expect($result)->toBe('tag-purge-id');

    Http::assertSent(function (Request $request): bool {
        return $request['tags'] === ['tag1', 'tag2'];
    });
});

it('returns false from purgeByTags when purger is inactive', function (): void {
    $purger = new CloudflarePurger(array_merge($this->config, ['enabled' => false]), 'production');

    $result = $purger->purgeByTags(['tag1']);

    expect($result)->toBeFalse();
    Http::assertNothingSent();
});

it('purges everything and returns true', function (): void {
    Http::fake([
        'api.cloudflare.com/*' => Http::response(['success' => true, 'result' => ['id' => 'all-purge-id']], 200),
    ]);

    $result = $this->purger->purgeEverything();

    expect($result)->toBeTrue();

    Http::assertSent(function (Request $request): bool {
        return $request['purge_everything'] === true;
    });
});

it('returns false from purgeEverything when purger is inactive', function (): void {
    $purger = new CloudflarePurger(array_merge($this->config, ['enabled' => false]), 'production');

    $result = $purger->purgeEverything();

    expect($result)->toBeFalse();
    Http::assertNothingSent();
});

it('throws CloudflarePurgeException on failed http response with error message and code', function (): void {
    Http::fake([
        'api.cloudflare.com/*' => Http::response([
            'success' => false,
            'errors'  => [['message' => 'Invalid API token', 'code' => 1001]],
        ], 403),
    ]);

    expect(fn () => $this->purger->purgeEverything())->toThrow(
        CloudflarePurgeException::class,
        'Cloudflare request failed with status 403: Invalid API token (code: 1001)'
    );
});

it('throws CloudflarePurgeException on failed http response without error message uses reason', function (): void {
    Http::fake([
        'api.cloudflare.com/*' => Http::response([], 500),
    ]);

    expect(fn () => $this->purger->purgeEverything())->toThrow(CloudflarePurgeException::class);
});

it('returns false when success is false but http status is 200', function (): void {
    Http::fake([
        'api.cloudflare.com/*' => Http::response(['success' => false], 200),
    ]);

    $result = $this->purger->purgeEverything();

    expect($result)->toBeFalse();
});

it('sends X-Auth-Email and X-Auth-Key headers when using legacy auth', function (): void {
    $purger = new CloudflarePurger(array_merge($this->config, [
        'api_token' => null,
        'api_email' => 'admin@example.com',
        'api_key'   => 'my-global-key',
    ]), 'production');

    Http::fake([
        'api.cloudflare.com/*' => Http::response(['success' => true], 200),
    ]);

    $purger->purgeEverything();

    Http::assertSent(function (Request $request): bool {
        return $request->hasHeader('X-Auth-Email', 'admin@example.com')
            && $request->hasHeader('X-Auth-Key', 'my-global-key');
    });
});

it('sends Authorization Bearer header when using api_token', function (): void {
    Http::fake([
        'api.cloudflare.com/*' => Http::response(['success' => true], 200),
    ]);

    $this->purger->purgeEverything();

    Http::assertSent(function (Request $request): bool {
        return $request->hasHeader('Authorization', 'Bearer test-api-token');
    });
});

it('logs debug request and response when debug is enabled', function (): void {
    $purger = new CloudflarePurger(array_merge($this->config, ['debug' => true]), 'production');

    Http::fake([
        'api.cloudflare.com/*' => Http::response(['success' => true], 200),
    ]);

    Log::shouldReceive('debug')
        ->twice()
        ->withArgs(function (string $message): bool {
            return in_array($message, ['Cloudflare Purge Request', 'Cloudflare Purge Response'], true);
        });

    $purger->purgeEverything();
});

it('does not log when debug is disabled', function (): void {
    Http::fake([
        'api.cloudflare.com/*' => Http::response(['success' => true], 200),
    ]);

    Log::shouldReceive('debug')->never();

    $this->purger->purgeEverything();
});

it('resolves via facade and purges everything', function (): void {
    Http::fake([
        'api.cloudflare.com/*' => Http::response(['success' => true], 200),
    ]);

    $result = CloudflarePurgerFacade::purgeEverything();

    expect($result)->toBeTrue();
});

it('resolves via facade and reports isActive', function (): void {
    $result = CloudflarePurgerFacade::isActive();

    expect($result)->toBeTrue();
});
