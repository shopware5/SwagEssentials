<?php declare(strict_types=1);

namespace SwagEssentials\PrimaryReplica\Subscriber;

use Enlight\Event\SubscriberInterface;
use SwagEssentials\PrimaryReplica\Commands\RunSql;
use Doctrine\Common\Collections\ArrayCollection;
use SwagEssentials\PrimaryReplica\ConnectionDecision;
use SwagEssentials\PrimaryReplica\PdoFactory;

class Bridge implements SubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Console_Add_Command' => 'addConsoleCommands',
            'Enlight_Controller_Front_PreDispatch' => 'startDispatch',
            'Enlight_Controller_Front_DispatchLoopShutdown' => 'dispatchShutdown',
        ];
    }

    public function addConsoleCommands()
    {
        return new ArrayCollection(
            [
                new RunSql()
            ]
        );
    }

    public function startDispatch()
    {
        if (Shopware()->Container()->has('shop')) {
            PdoFactory::$connectionDecision->setPinnedTables(
                array_merge(
                    Shopware()->Session()->get('tables', []),
                    PdoFactory::$connectionDecision->getPinnedTables()
                )
            );
        }

        if (Shopware()->Container()->has('backendsession')) {
            PdoFactory::$connectionDecision->setPinnedTables(
                array_merge(
                    Shopware()->BackendSession()->get('tables', []),
                    PdoFactory::$connectionDecision->getPinnedTables()
                )
            );
        }
    }

    public function dispatchShutdown()
    {
        if (Shopware()->Container()->has('shop')) {
            /** @var ConnectionDecision $decision */
            Shopware()->Session()->offsetSet('tables', PdoFactory::$connectionDecision->getPinnedTables());
        }

        if (Shopware()->Container()->has('backendsession')) {
            Shopware()->BackendSession()->offsetSet('tables', PdoFactory::$connectionDecision->getPinnedTables());
        }
    }
}
