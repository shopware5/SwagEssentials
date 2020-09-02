<?php declare(strict_types=1);

namespace SwagEssentials\Redis\Store;

use Shopware\Components\CacheManager as ShopwareCacheManager;
use Shopware\Components\DependencyInjection\Container;
use SwagEssentials\Common\CacheManagerDecorationTrait;

class CacheManager extends ShopwareCacheManager
{
    use CacheManagerDecorationTrait;

    /**
     * @var ShopwareCacheManager
     */
    private $innerCacheManager;

    /**
     * @var RedisStore
     */
    private $httpCache;

    /**
     * @param Container $container
     * @param ShopwareCacheManager $innerCacheManager
     */
    public function __construct()
    {
        $args = func_get_args();
        $this->innerCacheManager = array_shift($args);
        $this->httpCache = array_shift($args);

        parent::__construct(...$args);
    }

    /**
     * Returns cache information
     *
     * @param \Enlight_Controller_Request_RequestHttp $request
     * @return array
     * @throws \Exception
     */
    public function getHttpCacheInfo($request = null): array
    {
        if (!$this->httpCache || !$this->httpCache->getStore() instanceof RedisStore) {
            return [];
        }

        $cacheInfo = $this->httpCache->getStore()->getCacheInfo();

        $info = [
            'size' => $this->encodeSize($cacheInfo['size']),
            'files' => $cacheInfo['entries'],
            'message' => '',
            'dir' => '',
            'freeSpace' => $cacheInfo['freeSpace'],
        ];

        $info['name'] = 'Redis HTTP Cache';

        if ($request && $request->getHeader('Surrogate-Capability')) {
            $info['backend'] = $request->getHeader('Surrogate-Capability');
        } else {
            $info['backend'] = 'No Surrogate-Capability';
        }

        return $info;
    }

    public function clearHttpCache()
    {
        $this->innerCacheManager->clearHttpCache();
        if ($this->httpCache && $this->httpCache->getStore() instanceof RedisStore) {
            $this->httpCache->getStore()->purgeAll();
        }
    }
}
