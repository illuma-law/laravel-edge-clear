<?php

declare(strict_types=1);

use IllumaLaw\EdgeClear\Middleware\BypassEdgeCache;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

it('adds bypass headers to the response', function (): void {
    $middleware = new BypassEdgeCache;
    $request = Request::create('/', 'GET');

    $response = $middleware->handle($request, function () {
        return new Response;
    });

    expect($response->headers->get('Cache-Control'))->toContain('no-cache');
    expect($response->headers->get('Cache-Control'))->toContain('no-store');
    expect($response->headers->get('Cache-Control'))->toContain('must-revalidate');
    expect($response->headers->get('Pragma'))->toBe('no-cache');
    expect($response->headers->get('Expires'))->toBe('0');
});
