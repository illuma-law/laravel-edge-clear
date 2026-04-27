<?php

declare(strict_types=1);

namespace IllumaLaw\EdgeClear;

use IllumaLaw\EdgeClear\Exceptions\CloudflarePurgeException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final readonly class CloudflarePurger
{
    /**
     * @param array{
     *     zone_id: ?string,
     *     api_token: ?string,
     *     api_email: ?string,
     *     api_key: ?string,
     *     enabled: bool,
     *     only_in_production: bool,
     *     debug: bool
     * } $config
     */
    public function __construct(
        private array $config,
        private string $environment
    ) {}

    public function isActive(): bool
    {
        if (! $this->config['enabled']) {
            return false;
        }

        if ($this->config['only_in_production'] && $this->environment !== 'production') {
            return false;
        }

        return ! empty($this->config['zone_id']) &&
            (! empty($this->config['api_token']) || (! empty($this->config['api_email']) && ! empty($this->config['api_key'])));
    }

    /**
     * @param  array<int, string>  $urls
     *
     * @throws CloudflarePurgeException
     */
    public function purgeByUrls(array $urls): bool|string
    {
        return $this->sendPurgeRequest(['files' => array_values($urls)]);
    }

    /**
     * @param  array<int, string>  $tags
     *
     * @throws CloudflarePurgeException
     */
    public function purgeByTags(array $tags): bool|string
    {
        return $this->sendPurgeRequest(['tags' => array_values($tags)]);
    }

    /**
     * @throws CloudflarePurgeException
     */
    public function purgeEverything(): bool
    {
        return (bool) $this->sendPurgeRequest(['purge_everything' => true]);
    }

    /**
     * @param  array<string, mixed>  $body
     *
     * @throws CloudflarePurgeException
     */
    private function sendPurgeRequest(array $body): bool|string
    {
        if (! $this->isActive()) {
            return false;
        }

        $zoneId = $this->config['zone_id'];
        $url = "https://api.cloudflare.com/client/v4/zones/{$zoneId}/purge_cache";

        if (! empty($this->config['api_token'])) {
            $request = Http::asJson()->withToken($this->config['api_token']);
        } else {
            $request = Http::asJson()->withHeaders([
                'X-Auth-Email' => $this->config['api_email'],
                'X-Auth-Key'   => $this->config['api_key'],
            ]);
        }

        if ($this->config['debug']) {
            Log::debug('Cloudflare Purge Request', [
                'url'  => $url,
                'body' => $body,
            ]);
        }

        $response = $request->post($url, $body);

        if ($this->config['debug']) {
            Log::debug('Cloudflare Purge Response', [
                'status' => $response->status(),
                'body'   => $response->json(),
            ]);
        }

        return $this->handleResponse($response);
    }

    /**
     * @throws CloudflarePurgeException
     */
    private function handleResponse(Response $response): bool|string
    {
        if ($response->failed()) {
            $error = is_string($response->json('errors.0.message'))
                ? $response->json('errors.0.message')
                : (string) $response->reason();
            $code = $response->json('errors.0.code');
            $code = is_int($code) || is_string($code) ? $code : null;

            throw CloudflarePurgeException::requestError(
                $response->status(),
                $error,
                $code
            );
        }

        if (! $response->json('success')) {
            return false;
        }

        $resultId = $response->json('result.id');

        return is_string($resultId) ? $resultId : true;
    }
}
