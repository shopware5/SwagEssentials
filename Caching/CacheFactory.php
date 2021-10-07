<?php

declare(strict_types=1);

namespace SwagEssentials\Caching;

use Shopware\Bundle\StoreFrontBundle\Service\ListProductServiceInterface;
use Shopware\Bundle\StoreFrontBundle\Service\ProductServiceInterface;
use SwagEssentials\Caching\Caches\CachingListProductDecorator;
use SwagEssentials\Caching\Caches\CachingProductDecorator;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CacheFactory
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return ListProductServiceInterface
     */
    public function createListProductService(ListProductServiceInterface $listProductService)
    {
        if (!$this->container->getParameter('shopware.swag_essentials.caching_enable_list_product')) {
            return $listProductService;
        }

        return new CachingListProductDecorator(
            $this->container->get('cache'),
            $listProductService,
            $this->container->getParameter('shopware.swag_essentials.caching_ttl_list_product')
        );
    }

    /**
     * @return ProductServiceInterface
     */
    public function createProductService(ProductServiceInterface $productService)
    {
        if (!$this->container->getParameter('shopware.swag_essentials.caching_enable_product')) {
            return $productService;
        }

        return new CachingProductDecorator(
            $this->container->get('cache'),
            $productService,
            $this->container->getParameter('shopware.swag_essentials.caching_ttl_product')
        );
    }
}
