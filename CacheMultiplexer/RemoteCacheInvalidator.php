<?php

namespace SwagEssentials\CacheMultiplexer;


use Shopware\Components\Logger;
use SwagEssentials\CacheMultiplexer\Api\Client;

/**
 * Class RemoteCacheInvalidator will call a given list of app servers via API in order to invalidate the given caches
 */
class RemoteCacheInvalidator
{
    const ENDPOINT_HOST = 'host';
    const ENDPOINT_USER = 'user';
    const ENDPOINT_PASSWORD = 'password';

    /**
     * List of all hosts
     * @var array[]
     */
    private $hosts;

    /**
     * Core logger
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
                $response = $this->getClientForEndpoint($endpoint)->delete('caches/', $caches);

                if ($response->getCode() < 200 || $response->getCode() >= 300) {
                    $error = ['body' => $response->getRawBody(), 'code' => $response->getCode()];
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
     * @return Client
     */
    private function getClientForEndpoint($endpoint)
    {
        $client = new Client(
            $endpoint[self::ENDPOINT_HOST],
            $endpoint[self::ENDPOINT_USER],
            $endpoint[self::ENDPOINT_PASSWORD]
        );
        return $client;
    }

    /**
     * @param $caches
     * @return array
     */
    private function normalizeCacheIds($caches)
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