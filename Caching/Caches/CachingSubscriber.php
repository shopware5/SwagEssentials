<?php

declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagEssentials\Caching\Caches;

use Enlight\Event\SubscriberInterface;

class CachingSubscriber implements SubscriberInterface
{
    /**
     * @var \Shopware_Components_Config
     */
    protected $config;

    /**
     * @var \Zend_Cache_Core
     */
    protected $cache;

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
        ];
    }

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

        if ($this->cache->getBackend() instanceof \Zend_Cache_Backend_Apcu) {
            $this->cache->clean(\Zend_Cache::CLEANING_MODE_ALL);

            return;
        }

        $this->cache->clean(\Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, ['swag_essentials_product', 'swag_essentials_list_product']);
    }
}
