<?php

declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
