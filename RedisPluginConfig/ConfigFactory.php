<?php

namespace SwagEssentials\RedisPluginConfig;

use Shopware\Components\DependencyInjection\Bridge\Config as ShopwareConfig;

class ConfigFactory extends ShopwareConfig
{
    /**
     * @var \Redis
     */
    private $redis;

    public function __construct(\Redis $redis)
    {
        $this->redis = $redis;
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

        return new Config($config);
    }
}
