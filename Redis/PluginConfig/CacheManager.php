<?php

declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagEssentials\Redis\PluginConfig;

use SwagEssentials\Common\CacheManagerDecorationTrait;

class CacheManager extends \Shopware\Components\CacheManager
{
    use CacheManagerDecorationTrait;

    /**
     * @var \Shopware\Components\CacheManager
     */
    protected $innerCacheManager;

    /**
     * @var \Redis
     */
    protected $redis;

    protected $config;

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
