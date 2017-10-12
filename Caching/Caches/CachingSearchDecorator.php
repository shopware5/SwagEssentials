<?php declare(strict_types=1);

namespace SwagEssentials\Caching\Caches;

use Shopware\Bundle\SearchBundle\Criteria;
use Shopware\Bundle\SearchBundle\ProductSearchInterface;
use Shopware\Bundle\StoreFrontBundle\Struct;

class CachingSearchDecorator implements ProductSearchInterface
{
    /**
     * @var \Zend_Cache_Core
     */
    private $cache;

    /**
     * @var ProductSearchInterface The previously existing service
     */
    private $service;

    /**
     * @var int
     */
    private $ttl;

    /**
     * @param \Zend_Cache_Core $cache
     * @param ProductSearchInterface $service
     * @param $ttl
     */
    public function __construct(\Zend_Cache_Core $cache, ProductSearchInterface $service, $ttl)
    {
        $this->service = $service;

        $this->cache = $cache;
        $this->ttl = $ttl;
    }

    public function search(Criteria $criteria, Struct\ProductContextInterface $context)
    {
        $hash = md5(serialize(['search', $criteria, $context]));

        if ($cache = $this->cache->load($hash)) {
            return $cache;
        }

        $result = $this->service->search($criteria, $context);
        $this->cache->save($result, $hash, [], $this->ttl);
        return $result;
    }
}

