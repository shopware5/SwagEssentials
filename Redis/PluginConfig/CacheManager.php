<?php declare(strict_types=1);

namespace SwagEssentials\Redis\PluginConfig;

use Shopware\Components\DependencyInjection\Container;
use SwagEssentials\Common\CacheManagerDecorationTrait;

class CacheManager extends \Shopware\Components\CacheManager
{
    use CacheManagerDecorationTrait;

    /**
     * @var \Shopware\Components\CacheManager
     */
    private $innerCacheManager;

    /**
     * @var \Redis
     */
    private $redis;

    public function __construct(Container $container, \Shopware\Components\CacheManager $innerCacheManager)
    {
        parent::__construct($container);
        $this->innerCacheManager = $innerCacheManager;
        $this->redis = $container->get('swag_essentials.redis');
    }

    public function clearConfigCache()
    {
        $this->innerCacheManager->clearConfigCache();
        $this->redis->delete(Config::HASH_NAME, Reader::HASH_NAME);
    }
}
