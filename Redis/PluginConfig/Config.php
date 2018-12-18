<?php declare(strict_types=1);

namespace SwagEssentials\Redis\PluginConfig;

use Shopware;

class Config extends \Shopware_Components_Config
{
    /**
     * @var string
     */
    private $hashName = Shopware::VERSION . '-sw_config_core';

    /**
     * @var \Redis
     */
    private $redis;

    /**
     * @var int $cachingTtlPluginConfig
     */
    private $cachingTtlPluginConfig;

    /**
     * @var array
     */
    private $config;

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
            $this->hashName = str_replace(Shopware::VERSION, $config['release']->getVersion(), $this->hashName);
        }

        $this->config = $config;
        parent::__construct($config);
    }

    /**
     * Read data with translations from database
     *
     * @return array
     */
    protected function readData(): array
    {
        $parameters = [
            'fallbackShopId' => 1, //Shop parent id
            'parentShopId' => isset($this->_shop) && $this->_shop->getMain() !== null ? $this->_shop->getMain()->getId(
            ) : 1,
            'currentShopId' => isset($this->_shop) ? $this->_shop->getId() : null,
        ];

        $key = implode('|', $parameters);

        $result = $this->redis->hGet($this->hashName, $key);

        if ($result) {
            return json_decode($result, true);
        }

        $sql = '
            SELECT
              LOWER(REPLACE(e.name, \'_\', \'\')) AS name,
              COALESCE(currentShop.value, parentShop.value, fallbackShop.value, e.value) AS value,
              LOWER(REPLACE(forms.name, \'_\', \'\')) AS form,
              currentShop.value AS currentShopval,
              parentShop.value AS parentShopval,
              fallbackShop.value AS fallbackShopval

            FROM s_core_config_elements e

            LEFT JOIN s_core_config_values currentShop
              ON currentShop.element_id = e.id
              AND currentShop.shop_id = :currentShopId

            LEFT JOIN s_core_config_values parentShop
              ON parentShop.element_id = e.id
              AND parentShop.shop_id = :parentShopId

            LEFT JOIN s_core_config_values fallbackShop
              ON fallbackShop.element_id = e.id
              AND fallbackShop.shop_id = :fallbackShopId

            LEFT JOIN s_core_config_forms forms
              ON forms.id = e.form_id
        ';

        $data = $this->_db->fetchAll(
            $sql,
            $parameters
        );

        $result = [];
        foreach ($data as $row) {
            $value = !empty($row['value']) ? @unserialize($row['value'], ['allowed_classes' => true]) : null;
            $result[$row['name']] = $value;
            // Take namespaces (form names) into account
            $result[$row['form'] . '::' . $row['name']] = $value;
        }

        $result['version'] = Shopware::VERSION;
        $result['revision'] = Shopware::REVISION;
        $result['versiontext'] = Shopware::VERSION_TEXT;

        if (isset($this->config['release'])) {
            $result['version'] = $this->config['release']->getVersion();
            $result['revision'] = $this->config['release']->getRevision();
            $result['versiontext'] = $this->config['release']->getVersionText();
        }

        $this->redis->hSet($this->hashName, $key, json_encode($result));
        $this->redis->expire($this->hashName, $this->cachingTtlPluginConfig);

        return $result;
    }

    protected function load()
    {
        $this->_data = $this->readData();
    }
}
