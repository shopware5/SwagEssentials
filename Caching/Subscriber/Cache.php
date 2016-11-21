<?php

namespace SwagEssentials\Caching\Subscriber;

use Enlight\Event\SubscriberInterface;
use Shopware\Components\Routing\Generators\RewriteGenerator;
use Shopware\Components\Routing\Router;
use SwagEssentials\Caching\Caches\CachingListProductDecorator;
use SwagEssentials\Caching\Caches\CachingProductDecorator;
use SwagEssentials\Caching\Caches\CachingRewriteGeneratorDecorator;
use SwagEssentials\Caching\Caches\CachingSearchDecorator;

class Cache implements SubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            'Enlight_Bootstrap_AfterInitResource_shopware_storefront.list_product_service' => 'onDecorateListProduct',
            'Enlight_Bootstrap_AfterInitResource_shopware_storefront.product_service' => 'onDecorateProduct',
            'Enlight_Bootstrap_AfterInitResource_shopware_search.product_search' => 'onDecorateSearch',
            'Enlight_Controller_Front_RouteShutdown' => 'onDecorateRouter'
        );
    }

    public function onDecorateRouter()
    {

        if (!Shopware()->Container()->getParameter('swag_essentials.caching_enable_urls')) {
            return;
        }

        if (!Shopware()->Container()->has('router')) {
            return;
        }
        /** @var Router $router */
        $router = Shopware()->Container()->get('router');

        $generators = $router->getGenerators();

        foreach ($generators as &$generator) {
            if ($generator instanceof RewriteGenerator && !$generator instanceof CachingRewriteGeneratorDecorator) {
                $generator = new CachingRewriteGeneratorDecorator(Shopware()->Cache(), $generator, Shopware()->Container()->getParameter('swag_essentials.caching_ttl_urls'));
            }
        }

        $router->setGenerators($generators);
    }
    
    public function onDecorateListProduct()
    {

        if (!Shopware()->Container()->getParameter('swag_essentials.caching_enable_list_product')) {
            return;
        }

        $coreService  = Shopware()->Container()->get('shopware_storefront.list_product_service');

        if ($coreService instanceof CachingListProductDecorator) {
            return;
        }

        Shopware()->Container()->set('shopware_storefront.list_product_service', new CachingListProductDecorator(
            Shopware()->Cache(),
            $coreService,
            Shopware()->Container()->getParameter('swag_essentials.caching_ttl_list_product')
        ));

    }

    public function onDecorateProduct()
    {

        if (!Shopware()->Container()->getParameter('swag_essentials.caching_enable_product')) {
            return;
        }

        $coreService  = Shopware()->Container()->get('shopware_storefront.product_service');

        if ($coreService instanceof CachingProductDecorator) {
            return;
        }

        Shopware()->Container()->set('shopware_storefront.product_service', new CachingProductDecorator(
            Shopware()->Cache(),
            $coreService,
            Shopware()->Container()->getParameter('swag_essentials.caching_ttl_product')
        ));

    }

    public function onDecorateSearch()
    {

        if (!Shopware()->Container()->getParameter('swag_essentials.caching_enable_search')) {
            return;
        }

        $coreService  = Shopware()->Container()->get('shopware_search.product_search');

        if ($coreService instanceof CachingSearchDecorator) {
            return;
        }

        Shopware()->Container()->set('shopware_search.product_search', new CachingSearchDecorator(
            Shopware()->Cache(),
            $coreService,
            Shopware()->Container()->getParameter('swag_essentials.caching_ttl_search')
        ));

    }
}
