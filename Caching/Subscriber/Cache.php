<?php declare(strict_types=1);

namespace SwagEssentials\Caching\Subscriber;

use Enlight\Event\SubscriberInterface;
use Shopware\Components\Routing\Generators\RewriteGenerator;
use Shopware\Components\Routing\Router;
use SwagEssentials\Caching\Caches\CachingRewriteGeneratorDecorator;
use Zend_Cache_Core;

class Cache implements SubscriberInterface
{
    /**
     * @var Zend_Cache_Core
     */
    private $cache;

    /**
     * @var string
     */
    private $enabledCaching;

    /**
     * @var string
     */
    private $ttl;

    /**
     * @var Router
     */
    private $router;

    /**
     * @param Zend_Cache_Core $cache
     * @param Router $router
     * @param bool $enabledCaching
     * @param int $ttl
     */
    public function __construct(Zend_Cache_Core $cache, Router $router, bool $enabledCaching, int $ttl)
    {
        $this->cache = $cache;
        $this->enabledCaching = $enabledCaching;
        $this->ttl = $ttl;
        $this->router = $router;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'Enlight_Controller_Front_RouteShutdown' => 'onDecorateRouter',
        ];
    }

    public function onDecorateRouter()
    {
        if (!$this->enabledCaching) {
            return;
        }

        if (!$this->router) {
            return;
        }

        $generators = $this->router->getGenerators();

        foreach ($generators as &$generator) {
            if ($generator instanceof RewriteGenerator && !$generator instanceof CachingRewriteGeneratorDecorator) {
                $generator = new CachingRewriteGeneratorDecorator(
                    $this->cache,
                    $generator,
                    $this->ttl
                );
            }
        }
        unset($generator);

        $this->router->setGenerators($generators);
    }
}
