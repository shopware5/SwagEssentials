<?php

declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagEssentials\Redis\PluginConfig;

class Config extends \Shopware_Components_Config
{
    /**
     * @var string
     */
    public $hashName = '-sw_config_core';

    /**
     * @var \Redis
     */
    protected $redis;

    /**
     * @var int
     */
    protected $cachingTtlPluginConfig;

    /**
     * @var array
     */
    protected $config;

    /**
     * Constructor method
     *
     * @param array $config
     */
    public function __construct($config)
    {
        $this->redis = $config['redis'];

        $this->cachingTtlPluginConfig = $config['caching_ttl_plugin_config'];

        if (isset($config['release'])) {
            $this->hashName = $config['release']->getVersion() . $this->hashName;
        }

        $this->config = $config;
        parent::__construct($config);
    }

    /**
     * Read data with translations from database
     */
    protected function readData(): array
    {
        $parameters = [
            'fallbackShopId' => 1, // Shop parent id
            'parentShopId' => isset($this->_shop) && $this->_shop->getMain() !== null ? $this->_shop->getMain()->getId(
            ) : 1,
            'currentShopId' => isset($this->_shop) ? $this->_shop->getId() : null,
        ];

        $key = implode('|', $parameters);

        $result = $this->redis->hGet($this->hashName, $key);

        if ($result) {
            return json_decode($result, true);
        }

        $result = parent::readData();

        $this->redis->hSet($this->hashName, $key, json_encode($result));
        $this->redis->expire($this->hashName, $this->cachingTtlPluginConfig);

        return $result;
    }

    protected function load()
    {
        $this->_data = $this->readData();
    }
}
