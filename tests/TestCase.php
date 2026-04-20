<?php

declare(strict_types=1);

namespace IllumaLaw\EdgeClear\Tests;

use IllumaLaw\EdgeClear\EdgeClearServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            EdgeClearServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('edge-clear.zone_id', 'test-zone-id');
        $app['config']->set('edge-clear.api_token', 'test-api-token');
        $app['config']->set('edge-clear.enabled', true);
        $app['config']->set('edge-clear.only_in_production', false);
    }
}
