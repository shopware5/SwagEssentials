<?php declare(strict_types=1);

namespace SwagEssentials\Caching\Caches;

use Enlight\Event\SubscriberInterface;
use Shopware\Bundle\StoreFrontBundle\Service\ProductServiceInterface;

class CachingSubscriber implements SubscriberInterface
{
    /**
     * @var \Shopware_Components_Config
     */
    private $config;

    /**
     * @param \Zend_Cache_Core $cache
     * @param ProductServiceInterface $service
     * @param int $ttl
     */
    public function __construct(\Zend_Cache_Core $cache, \Shopware_Components_Config $config)
    {
        $this->cache = $cache;
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
            'Shopware\Models\Category\Category::postPersist' => 'onPostPersist',
            'Shopware\Models\Category\Category::postUpdate' => 'onPostPersist',
            'Shopware\Models\Banner\Banner::postPersist' => 'onPostPersist',
            'Shopware\Models\Banner\Banner::postUpdate' => 'onPostPersist',
            'Shopware\Models\Blog\Blog::postPersist' => 'onPostPersist',
            'Shopware\Models\Blog\Blog::postUpdate' => 'onPostPersist',
            'Shopware\Models\Emotion\Emotion::postPersist' => 'onPostPersist',
            'Shopware\Models\Emotion\Emotion::postUpdate' => 'onPostPersist',
            'Shopware\Models\Site\Site::postPersist' => 'onPostPersist',
            'Shopware\Models\Site\Site::postUpdate' => 'onPostPersist',
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
                'entityName' => $entityName
            ]
        )) {
            return;
        }

        $this->cache->clean(\Zend_Cache::CLEANING_MODE_MATCHING_TAG, ['swag_essentials_product', 'swag_essentials_list_product']);
    }
}
