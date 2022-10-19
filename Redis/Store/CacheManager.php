<?php

declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
     * @param Container            $container
     * @param ShopwareCacheManager $innerCacheManager
     */
    public function __construct()
    {
        $args = func_get_args();
        $this->innerCacheManager = array_shift($args);
        $this->redisStore = new RedisStore(array_shift($args));

        parent::__construct(...$args);
    }

    /**
     * Returns cache information
     *
     * @param \Enlight_Controller_Request_RequestHttp $request
     *
     * @throws \Exception
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
