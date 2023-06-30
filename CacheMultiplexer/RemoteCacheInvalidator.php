<?php

declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagEssentials\CacheMultiplexer;

use GuzzleHttp\Client;
use Shopware\Components\Logger;

/**
 * Class RemoteCacheInvalidator will call a given list of app servers via API in order to invalidate the given caches
 */
class RemoteCacheInvalidator
{
    public const ENDPOINT_HOST = 'host';
    public const ENDPOINT_HEADERS = 'headers';
    public const ENDPOINT_USER = 'user';
    public const ENDPOINT_PASSWORD = 'password';
    public const ENDPOINT_AUTHMETHOD = 'authMethod';

    /**
     * List of all hosts
     *
     * @var array[]
     */
    protected $hosts;

    /**
     * Core logger
     *
     * @var Logger
     */
    protected $logger;

    public function __construct($hosts, Logger $logger)
    {
        $this->hosts = $hosts;
        $this->logger = $logger;
    }

    /**
     * Iterate all endpoints and clear all caches one by one
     *
     * @param string[] $caches
     */
    public function remoteClear($caches)
    {
        $caches = $this->normalizeCacheIds($caches);

        foreach ($this->hosts as $endpoint) {
            $error = null;

            try {
                $response = $this->getResponseForEndpoint($endpoint, $caches);

                if ($response && ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300)) {
                    $error = ['body' => $response->getBody(), 'code' => $response->getStatusCode()];
                }

                if (!$response) {
                    $this->logger->error("Cache multiplexing failed for host {$endpoint[self::ENDPOINT_HOST]}", $error);
                }
            } catch (\Exception $e) {
                $error = ['message' => $e->getMessage()];
            }

            if ($error) {
                $this->logger->error("Cache multiplexing failed for host {$endpoint[self::ENDPOINT_HOST]}", $error);
            } else {
                $this->logger->debug("Invalidated host {$endpoint[self::ENDPOINT_HOST]}", $caches);
            }
        }
    }

    /**
     * Get an API client for a given endpoint
     *
     * @return \GuzzleHttp\Message\FutureResponse|\GuzzleHttp\Message\ResponseInterface|\GuzzleHttp\Ring\Future\FutureInterface|null
     */
    protected function getResponseForEndpoint($endpoint, $caches)
    {
        $client = new Client();
        $authMethod = $endpoint[self::ENDPOINT_AUTHMETHOD] ?? 'basic';
        $headers = $endpoint[self::ENDPOINT_HEADERS] ?? [];

        return $client->delete(
            $endpoint[self::ENDPOINT_HOST] . '/caches',
            [
                'auth' => [$endpoint[self::ENDPOINT_USER], $endpoint[self::ENDPOINT_PASSWORD], $authMethod],
                'json' => $caches,
                'headers' => $headers,
            ]
        );
    }

    protected function normalizeCacheIds($caches): array
    {
        $caches = array_map(
            static function ($cache) {
                return ['id' => $cache];
            },
            $caches
        );

        return $caches;
    }
}
