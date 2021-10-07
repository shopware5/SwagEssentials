<?php

declare(strict_types=1);

namespace SwagEssentials\CacheMultiplexer\Subscriber;

use Enlight\Event\SubscriberInterface;
use SwagEssentials\CacheMultiplexer\RemoteCacheInvalidator;

class BackendCache implements SubscriberInterface
{
    /**
     * @var RemoteCacheInvalidator
     */
    protected $cacheInvalidator;

    public function __construct(RemoteCacheInvalidator $cacheInvalidator)
    {
        $this->cacheInvalidator = $cacheInvalidator;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Controllers_Backend_Cache::moveThemeFilesAction::after' => 'onAfterMoveThemeFiles',
        ];
    }

    public function onAfterMoveThemeFiles(\Enlight_Event_EventArgs $args)
    {
        $this->cacheInvalidator->remoteClear(['theme']);
    }
}
