<?php declare(strict_types=1);

namespace SwagEssentials\Caching\Subscriber;

use Enlight\Event\SubscriberInterface;
use Shopware\Components\Routing\Generators\RewriteGenerator;
use Shopware\Components\Routing\Router;
use SwagEssentials\Caching\Caches\CachingRewriteGeneratorDecorator;

class Cache implements SubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Front_RouteShutdown' => 'onDecorateRouter'
        ];
    }

    public function onDecorateRouter()
    {
        if (!Shopware()->Container()->getParameter('shopware.swag_essentials.caching_enable_urls')) {
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
                $generator = new CachingRewriteGeneratorDecorator(
                    Shopware()->Cache(),
                    $generator,
                    Shopware()->Container()->getParameter('shopware.swag_essentials.caching_ttl_urls')
                );
            }
        }
        unset($generator);

        $router->setGenerators($generators);
    }
}
