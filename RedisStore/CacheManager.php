<?php

namespace SwagEssentials\RedisStore;

use Shopware\Components\DependencyInjection\Container;
use Shopware\Components\HttpCache\AppCache;
use SwagEssentials\Common\CacheManagerDecorationTrait;

class CacheManager extends \Shopware\Components\CacheManager
{
    use CacheManagerDecorationTrait;

    private $innerCacheManager;
    /** @var AppCache */
    private $httpCache = null;
    private $container;

    public function __construct(Container $container, \Shopware\Components\CacheManager $innerCacheManager)
    {
        parent::__construct($container);
        if ($container->has('httpCache')) {
            $this->httpCache = $container->get("httpCache");
        }
        $this->innerCacheManager = $innerCacheManager;
        $this->container = $container;
    }


    /**
     * Returns cache information
     *
     * @param null $request
     *
     * @return array
     */
    public function getHttpCacheInfo($request = null)
    {
        if (!$this->httpCache || !$this->httpCache->getStore() instanceof RedisStore) {
            return [];
        }

        $cacheInfo = $this->httpCache->getStore()->getCacheInfo();

        $info = [
            'size' => $this->encodeSize($cacheInfo['size']),
            'files' => $cacheInfo['entries'],
            'message' => '',
            'dir' => '',
            'freeSpace' => ''
        ];

        $info['name'] = 'Redis HTTP Cache';

        if ($request && $request->getHeader('Surrogate-Capability')) {
            $info['backend'] = $request->getHeader('Surrogate-Capability');
        } else {
            $info['backend'] = 'No Surrogate-Capability';
        }

        return $info;
    }
}
