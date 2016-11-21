<?php

namespace SwagEssentials\CacheMultiplexer\Subscriber;

use Enlight\Event\SubscriberInterface;
use SwagEssentials\CacheMultiplexer\Api\Client;

/**
 * Class CacheMultiplexer intercepts cache clear requests and multiplexes them to configured appservers
 * @package Shopware\SwagCacheMultiplexer\Subscriber
 */
class CacheMultiplexer implements SubscriberInterface
{
    const ENDPOINT_HOST = 'host';
    const ENDPOINT_USER = 'user';
    const ENDPOINT_PASSWORD = 'password';

    public static function getSubscribedEvents()
    {
        return array(
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Cache' => 'onBackendCache'
        );
    }

    public function onBackendCache(\Enlight_Event_EventArgs $args)
    {
        /** @var \Enlight_Controller_Request_RequestHttp $request */
        $request = $args->getSubject()->Request();

        switch ($request->getActionName()) {
            case 'clearCache':
                $keys = $this->clearCache($request);
                break;
            case 'clearDirect':
                $keys = $this->clearDirect();
                break;
            default:
                $keys = [];
        }

        $this->remoteClear($keys);
    }

    /**
     * Iterate all endpoints and clear all caches one by one
     *
     * @param $caches
     * @return bool
     */
    private function remoteClear($caches)
    {
        $caches = array_map(
            function ($cache) {
                return ['id' => $cache];
            },
            $caches
        );

        $endpoints = Shopware()->Container()->getParameter('swag_essentials.cache_multiplexer_hosts');

        foreach ($endpoints as $endpoint) {
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
                $this->logException($endpoint[self::ENDPOINT_HOST], $error);
            }
        }
    }

    /**
     * Extract affected caches from normal cache clear
     *
     * @param \Enlight_Controller_Request_RequestHttp $request
     * @return array
     */
    private function clearCache(\Enlight_Controller_Request_RequestHttp $request)
    {
        $caches = $request->getPost('cache', array());

        return array_keys(
            array_filter(
                $caches,
                function ($cache) {
                    return $cache == 'on';
                }
            )
        );
    }

    /**
     * Extract affected caches from quick cache clear
     *
     * @return array
     */
    private function clearDirect()
    {
        return [
            'http',
            'template',
            'config',
            'search',
            'proxy',
        ];
    }

    /**
     * Log an error
     *
     * @param $host
     * @param $context
     */
    private function logException($host, $context)
    {
        Shopware()->Container()->get('corelogger')->error(
            "Cache multiplexing failed for host {$host}",
            $context
        );
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
}