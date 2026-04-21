<?php

declare(strict_types=1);

namespace IllumaLaw\EdgeClear;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
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
        $this->app->singleton(CloudflarePurger::class, function (Application $app): CloudflarePurger {
            /** @var Repository $configRepository */
            $configRepository = $app->make('config');

            /** @var array{zone_id: ?string, api_token: ?string, api_email: ?string, api_key: ?string, enabled: bool, only_in_production: bool, debug: bool} $config */
            $config = $configRepository->get('edge-clear', []);

            return new CloudflarePurger(
                config: $config,
                environment: $app->environment()
            );
        });

        $this->app->alias(CloudflarePurger::class, 'cloudflare-purger');
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [CloudflarePurger::class, 'cloudflare-purger'];
    }
}
