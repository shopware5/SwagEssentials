<?php declare(strict_types=1);

namespace SwagEssentials\Redis\PluginConfig;

use Doctrine\DBAL\Connection;
use Shopware\Components\DependencyInjection\Bridge\Config as ShopwareConfig;
use Shopware\Components\ShopwareReleaseStruct;
use SwagEssentials\Redis\RedisConnection;

class Factory extends ShopwareConfig
{
    /**
     * @var RedisConnection
     */
    protected $redis;

    /**
     * @var int
     */
    protected $cachingTtlPluginConfig;

    public function __construct(RedisConnection $redis, int $cachingTtlPluginConfig)
    {
        $this->redis = $redis;
        $this->cachingTtlPluginConfig = $cachingTtlPluginConfig;
    }

    public function factory(
        \Zend_Cache_Core $cache,
        Connection $db = null,
        $config = [],
        ShopwareReleaseStruct $release = null
    ) {
        if (!$db) {
            return null;
        }

        if (!isset($config['cache'])) {
            $config['cache'] = $cache;
        }
        $config['db'] = $db;
        $config['release'] = $release;

        $config['redis'] = $this->redis;

        $config['caching_ttl_plugin_config'] = $this->cachingTtlPluginConfig;

        return new Config($config);
    }
}
