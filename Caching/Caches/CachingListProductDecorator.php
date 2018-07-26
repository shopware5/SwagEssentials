<?php declare(strict_types=1);

namespace SwagEssentials\Caching\Caches;

use Shopware\Bundle\StoreFrontBundle\Service\ListProductServiceInterface;
use Shopware\Bundle\StoreFrontBundle\Struct;

class CachingListProductDecorator implements ListProductServiceInterface
{
    /**
     * @var \Zend_Cache_Core
     */
    private $cache;

    /**
     * @var ListProductServiceInterface The previously existing service
     */
    private $service;

    /**
     * @var int
     */
    private $ttl;

    /**
     * @param \Zend_Cache_Core $cache
     * @param ListProductServiceInterface $service
     * @param int $ttl
     */
    public function __construct(\Zend_Cache_Core $cache, ListProductServiceInterface $service, $ttl)
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

        $hash = md5(serialize(['list_product', $numbers, $context]));

        if ($cache = $this->cache->load($hash)) {
            return $cache;
        }

        $result = $this->service->getList($numbers, $context);
        $this->cache->save($result, $hash, ['swag_essentials_list_product'], $this->ttl);

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
