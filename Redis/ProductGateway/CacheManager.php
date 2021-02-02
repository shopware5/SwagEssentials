<?php declare(strict_types=1);

namespace SwagEssentials\Redis\ProductGateway;

use Shopware\Components\CacheManager as ShopwareCacheManager;
use SwagEssentials\Common\CacheManagerDecorationTrait;
use SwagEssentials\Redis\RedisConnection;

class CacheManager extends ShopwareCacheManager
{
    use CacheManagerDecorationTrait;

    /**
     * @var ShopwareCacheManager
     */
    protected $innerCacheManager;

    /**
     * @var RedisConnection
     */
    protected $redis;

    public function __construct()
    {
        $args = func_get_args();
        $this->innerCacheManager = array_shift($args);
        $this->redis = array_shift($args);

        parent::__construct(...$args);
    }

    public function clearConfigCache()
    {
        $this->redis->del(ListProductService::HASH_NAME);

        $this->innerCacheManager->clearConfigCache();
    }
}
