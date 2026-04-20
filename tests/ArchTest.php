<?php

declare(strict_types=1);
use Illuminate\Support\Facades\Facade;
use Spatie\LaravelPackageTools\PackageServiceProvider;

arch('source classes use strict types')
    ->expect('IllumaLaw\EdgeClear')
    ->toUseStrictTypes();

arch('source classes have no debug statements')
    ->expect('IllumaLaw\EdgeClear')
    ->not->toUse(['dd', 'dump', 'var_dump', 'print_r', 'ray']);

arch('exceptions extend RuntimeException')
    ->expect('IllumaLaw\EdgeClear\Exceptions')
    ->toExtend(RuntimeException::class);

arch('facades extend Illuminate Facade')
    ->expect('IllumaLaw\EdgeClear\Facades')
    ->toExtend(Facade::class);

arch('middleware implements handle method')
    ->expect('IllumaLaw\EdgeClear\Middleware')
    ->toHaveMethod('handle');

arch('service provider extends PackageServiceProvider')
    ->expect('IllumaLaw\EdgeClear\EdgeClearServiceProvider')
    ->toExtend(PackageServiceProvider::class);
