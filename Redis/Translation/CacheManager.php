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
    private $innerCacheManager;

    /**
     * @var \Redis
     */
    private $redis;

    public function __construct(Container $container, ShopwareCacheManager $innerCacheManager)
    {
        parent::__construct($container);
        $this->innerCacheManager = $innerCacheManager;
        $this->redis = $container->get('swag_essentials.redis');
    }

    public function clearConfigCache()
    {
        $this->redis->del(Translation::HASH_NAME);

        $this->innerCacheManager->clearConfigCache();
    }
}
