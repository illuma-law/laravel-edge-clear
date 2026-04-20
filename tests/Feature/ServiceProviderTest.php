<?php

declare(strict_types=1);

use IllumaLaw\EdgeClear\CloudflarePurger;
use IllumaLaw\EdgeClear\EdgeClearServiceProvider;

it('registers the cloudflare-purger singleton', function (): void {
    expect(app('cloudflare-purger'))->not->toBeNull();
});

it('returns the same instance each time (singleton)', function (): void {
    $first = app('cloudflare-purger');
    $second = app('cloudflare-purger');

    expect($first)->toBe($second);
});

it('provides the cloudflare-purger binding', function (): void {
    $provider = new EdgeClearServiceProvider(app());

    expect($provider->provides())->toContain('cloudflare-purger');
});

it('merges the edge-clear config', function (): void {
    expect(config('edge-clear'))->toBeArray()
        ->and(config('edge-clear.enabled'))->toBeTrue()
        ->and(config('edge-clear.only_in_production'))->toBeFalse();
});

it('publishes the config file', function (): void {
    $publishedPaths = EdgeClearServiceProvider::pathsToPublish();

    expect($publishedPaths)->not->toBeEmpty();


});
