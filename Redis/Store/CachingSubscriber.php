<?php declare(strict_types=1);

namespace SwagEssentials\Redis\Store;

use Enlight\Event\SubscriberInterface;
use Shopware\Bundle\StoreFrontBundle\Service\ProductServiceInterface;
use Shopware\Components\DependencyInjection\Container;
use Shopware\Components\HttpCache\AppCache;

class CachingSubscriber implements SubscriberInterface
{
    /**
     * @var \Shopware_Components_Config
     */
    private $config;

    /**
     * @var AppCache|null
     */
    private $httpCache;

    /**
     * @param \Zend_Cache_Core $cache
     * @param \Shopware_Components_Config $config
     * @param Container $container
     */
    public function __construct(Container $container, \Shopware_Components_Config $config)
    {
        if ($container->has('httpCache')) {
            $this->httpCache = $container->get('httpCache');
        } else {
            $kernel = $container->get('kernel');
            if ($kernel->isHttpCacheEnabled()) {
                $this->httpCache = new AppCache($kernel, $kernel->getHttpCacheConfig());
            }
        }

        $this->config = $config;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopware\Models\Article\Price::postUpdate' => 'onPostPersist',
            'Shopware\Models\Article\Price::postPersist' => 'onPostPersist',
            'Shopware\Models\Article\Article::postUpdate' => 'onPostPersist',
            'Shopware\Models\Article\Article::postPersist' => 'onPostPersist',
            'Shopware\Models\Article\Detail::postUpdate' => 'onPostPersist',
            'Shopware\Models\Article\Detail::postPersist' => 'onPostPersist',
        ];
    }

    /**
     * @param \Enlight_Event_EventArgs $eventArgs
     */
    public function onPostPersist(\Enlight_Event_EventArgs $eventArgs)
    {
        if (!$this->config->get('proxyPrune')) {
            return;
        }

        $entity = $eventArgs->get('entity');
        if ($entity instanceof \Doctrine\ORM\Proxy\Proxy) {
            $entityName = get_parent_class($entity);
        } else {
            $entityName = get_class($entity);
        }

        if (Shopware()->Events()->notifyUntil(
            'Shopware_Plugins_HttpCache_ShouldNotInvalidateCache',
            [
                'entity' => $entity,
                'entityName' => $entityName,
            ]
        )) {
            return;
        }

        if ($this->httpCache && $this->httpCache->getStore() instanceof RedisStore) {
            $this->httpCache->getStore()->purgeAll();
        }
    }
}
