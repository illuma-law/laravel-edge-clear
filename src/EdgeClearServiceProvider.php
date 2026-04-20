<?php

declare(strict_types=1);

namespace IllumaLaw\EdgeClear;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class EdgeClearServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-edge-clear')
            ->hasConfigFile('edge-clear');
    }

    public function registeringPackage(): void
    {
        $this->app->singleton('cloudflare-purger', function ($app): CloudflarePurger {
            return new CloudflarePurger(
                config: $app['config']['edge-clear'],
                environment: $app->environment()
            );
        });
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return ['cloudflare-purger'];
    }
}
