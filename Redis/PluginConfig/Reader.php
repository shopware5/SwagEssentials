<?php declare(strict_types=1);

namespace SwagEssentials\Redis\PluginConfig;

use Shopware\Components\Plugin\ConfigReader as ConfigReaderInterface;
use Shopware\Components\Plugin\DBALConfigReader;
use Shopware\Models\Shop\Shop;
use SwagEssentials\Redis\RedisConnection;

class Reader implements ConfigReaderInterface
{
    const HASH_NAME = \Shopware::VERSION . 'sw_config';

    /**
     * @var DBALConfigReader
     */
    private $configReader;

    /**
     * @var RedisConnection
     */
    private $redis;

    /**
     * @var int
     */
    private $cachingTtlPluginConfig;

    public function __construct(DBALConfigReader $configReader, RedisConnection $redis, int $cachingTtlPluginConfig)
    {
        $this->configReader = $configReader;
        $this->redis = $redis;
        $this->cachingTtlPluginConfig = $cachingTtlPluginConfig;
    }

    /**
     * @param string $pluginName
     * @param Shop|null $shop
     * @return array
     */
    public function getByPluginName($pluginName, Shop $shop = null)
    {
        $key = [
            'pluginName' => $pluginName,
            'shopId' => $shop ? $shop->getId() : 0,
        ];

        $key = implode('|', $key);

        $result = $this->redis->hGet(self::HASH_NAME, $key);
        if ($result) {
            return json_decode($result, true);
        }

        $result = $this->configReader->getByPluginName($pluginName, $shop);

        $this->redis->hSet(self::HASH_NAME, $key, json_encode($result));
        $this->redis->expire(self::HASH_NAME, $this->cachingTtlPluginConfig);

        return $result;
    }
}
