<?php

declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
    protected $cache;

    /**
     * @var string
     */
    protected $enabledCaching;

    /**
     * @var string
     */
    protected $ttl;

    /**
     * @var Router
     */
    protected $router;

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
