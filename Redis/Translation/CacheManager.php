<?php declare(strict_types=1);

namespace SwagEssentials\Redis\Translation;

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
     * @var \Redis
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
        $this->redis->del(Translation::HASH_NAME);

        $this->innerCacheManager->clearConfigCache();
    }
}
