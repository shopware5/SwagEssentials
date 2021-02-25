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
    protected $innerCacheManager;

    /**
     * @var RedisStore
     */
    protected $redisStore;

    /**
     * @param ShopwareCacheManager $cacheManager
     * @param RedisStore $redisStore
     * @param $args
     */
    public function __construct(
        ShopwareCacheManager $cacheManager,
        RedisStore $redisStore,
        ...$args
    ) {
        $this->innerCacheManager = $cacheManager;
        $this->redisStore = $redisStore;

        parent::__construct(...$args);
    }

    /**
     * Returns cache information
     *
     * @param \Enlight_Controller_Request_RequestHttp $request
     * @throws \Exception
     * @return array
     */
    public function getHttpCacheInfo($request = null): array
    {
        $cacheInfo = $this->redisStore->getCacheInfo();

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
        $this->redisStore->purgeAll();
    }
}
