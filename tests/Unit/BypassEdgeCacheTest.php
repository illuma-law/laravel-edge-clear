<?php

declare(strict_types=1);

use IllumaLaw\EdgeClear\Middleware\BypassEdgeCache;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

it('sets no-cache headers on the response', function (): void {
    $middleware = new BypassEdgeCache;
    $request = Request::create('/test', 'GET');
    $response = new Response('OK', 200);

    $result = $middleware->handle($request, fn () => $response);

    expect($result->headers->get('Cache-Control'))->toContain('no-cache');
    expect($result->headers->get('Cache-Control'))->toContain('no-store');
    expect($result->headers->get('Cache-Control'))->toContain('must-revalidate');
    expect($result->headers->get('Pragma'))->toBe('no-cache');
    expect($result->headers->get('Expires'))->toBe('0');
});

it('passes the request to the next handler', function (): void {
    $middleware = new BypassEdgeCache;
    $request = Request::create('/test', 'GET');
    $called = false;

    $middleware->handle($request, function ($req) use (&$called, $request): Response {
        $called = true;
        expect($req)->toBe($request);

        return new Response('OK', 200);
    });

    expect($called)->toBeTrue();
});

it('preserves the original response body and status', function (): void {
    $middleware = new BypassEdgeCache;
    $request = Request::create('/test', 'GET');
    $response = new Response('Hello World', 201);

    $result = $middleware->handle($request, fn () => $response);

    expect($result->getContent())->toBe('Hello World');
    expect($result->getStatusCode())->toBe(201);
});
