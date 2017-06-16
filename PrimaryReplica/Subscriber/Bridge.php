<?php

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
        if (!Shopware()->Container()->has('shop')) {
            return;
        }

        PdoFactory::$connectionDecision->setPinnedTables(array_merge(Shopware()->Session()->get('tables', []), PdoFactory::$connectionDecision->getPinnedTables()));
    }

    public function dispatchShutdown()
    {

        if (!Shopware()->Container()->has('shop')) {
            return;
        }

        /** @var ConnectionDecision $decision */
        Shopware()->Session()->offsetSet('tables', PdoFactory::$connectionDecision->getPinnedTables());
    }


}