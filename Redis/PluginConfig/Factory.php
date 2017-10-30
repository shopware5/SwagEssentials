<?php declare(strict_types=1);

namespace SwagEssentials\Redis\PluginConfig;

use Shopware\Components\DependencyInjection\Bridge\Config as ShopwareConfig;

class Factory extends ShopwareConfig
{
    /**
     * @var \Redis
     */
    private $redis;

    /**
     * @var int
     */
    private $cachingTtlPluginConfig;

    public function __construct(\Redis $redis, int $cachingTtlPluginConfig)
    {
        $this->redis = $redis;
        $this->cachingTtlPluginConfig = $cachingTtlPluginConfig;
    }

    public function factory(
        \Zend_Cache_Core $cache,
        \Enlight_Components_Db_Adapter_Pdo_Mysql $db = null,
        $config = []
    ) {
        if (!$db) {
            return null;
        }

        if (!isset($config['cache'])) {
            $config['cache'] = $cache;
        }
        $config['db'] = $db;

        $config['redis'] = $this->redis;

        $config['caching_ttl_plugin_config'] = $this->cachingTtlPluginConfig;

        return new Config($config);
    }
}