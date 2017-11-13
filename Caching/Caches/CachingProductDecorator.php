<?php declare(strict_types=1);

namespace SwagEssentials\Caching\Caches;

use Shopware\Bundle\StoreFrontBundle\Service\ProductServiceInterface;
use Shopware\Bundle\StoreFrontBundle\Struct;

class CachingProductDecorator implements ProductServiceInterface
{
    /**
     * @var \Zend_Cache_Core
     */
    private $cache;

    /**
     * @var ProductServiceInterface The previously existing service
     */
    private $service;

    /**
     * @var int
     */
    private $ttl;

    /**
     * @param \Zend_Cache_Core $cache
     * @param ProductServiceInterface $service
     * @param int $ttl
     */
    public function __construct(\Zend_Cache_Core $cache, ProductServiceInterface $service, $ttl)
    {
        $this->service = $service;

        $this->cache = $cache;
        $this->ttl = $ttl;
    }

    /**
     * {@inheritdoc}
     */
    public function getList(array $numbers, Struct\ProductContextInterface $context)
    {
        if (empty($numbers)) {
            return [];
        }

        $hash = md5(serialize(['product', $numbers, $context]));

        if ($cache = $this->cache->load($hash)) {
            return $cache;
        }

        $result = $this->service->getList($numbers, $context);
        $this->cache->save($result, $hash, [], $this->ttl);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function get($number, Struct\ProductContextInterface $context)
    {
        $products = $this->getList([$number], $context);

        return array_shift($products);
    }
}
