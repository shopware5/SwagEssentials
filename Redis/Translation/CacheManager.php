<?php

declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagEssentials\Redis\Translation;

use Shopware\Components\CacheManager as ShopwareCacheManager;
use SwagEssentials\Common\CacheManagerDecorationTrait;

class CacheManager extends ShopwareCacheManager
{
    use CacheManagerDecorationTrait;

    /**
     * @var ShopwareCacheManager
     */
    protected $innerCacheManager;

    /**
     * @var \Redis
     */
    protected $redis;

    public function __construct()
    {
        $args = func_get_args();
        $this->innerCacheManager = array_shift($args);
        $this->redis = array_shift($args);

        parent::__construct(...$args);
    }

    public function clearConfigCache()
    {
        $this->redis->del(Translation::HASH_NAME);

        $this->innerCacheManager->clearConfigCache();
    }
}
