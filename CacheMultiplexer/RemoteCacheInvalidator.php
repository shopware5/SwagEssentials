<?php declare(strict_types=1);

namespace SwagEssentials\CacheMultiplexer;

use GuzzleHttp\Client;
use Shopware\Components\Logger;

/**
 * Class RemoteCacheInvalidator will call a given list of app servers via API in order to invalidate the given caches
 */
class RemoteCacheInvalidator
{
    const ENDPOINT_HOST = 'host';
    const ENDPOINT_USER = 'user';
    const ENDPOINT_PASSWORD = 'password';
    const ENDPOINT_AUTH = 'auth';

    /**
     * List of all hosts
     *
     * @var array[]
     */
    private $hosts;

    /**
     * Core logger
     *
     * @var Logger
     */
    private $logger;

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
     * @param $endpoint
     * @param $caches
     * @return \GuzzleHttp\Message\FutureResponse|\GuzzleHttp\Message\ResponseInterface|\GuzzleHttp\Ring\Future\FutureInterface|null
     */
    private function getResponseForEndpoint($endpoint, $caches)
    {
        $client = new Client();
        $authMethod = $endpoint[self::ENDPOINT_AUTH] ?? 'basic';
        return $client->delete(
            $endpoint[self::ENDPOINT_HOST] . '/caches',
            [
                'auth' => [$endpoint[self::ENDPOINT_USER], $endpoint[self::ENDPOINT_PASSWORD], $authMethod],
                'json' => $caches,
            ]
        );
    }

    /**
     * @param $caches
     * @return array
     */
    private function normalizeCacheIds($caches): array
    {
        $caches = array_map(
            function ($cache) {
                return ['id' => $cache];
            },
            $caches
        );

        return $caches;
    }
}
