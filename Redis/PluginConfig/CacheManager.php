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

    private $config;

    public function __construct()
    {
        $args = func_get_args();
        $this->innerCacheManager = array_shift($args);
        $this->redis = array_shift($args);
        $this->config = array_shift($args);

        parent::__construct(...$args);
    }

    public function clearConfigCache()
    {
        $this->innerCacheManager->clearConfigCache();
        $this->redis->delete($this->config->hashName, Reader::HASH_NAME);
    }
}
