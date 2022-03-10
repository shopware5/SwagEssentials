<?php

declare(strict_types=1);

namespace SwagEssentials\PrimaryReplica\Subscriber;

use Doctrine\Common\Collections\ArrayCollection;
use Enlight\Event\SubscriberInterface;
use SwagEssentials\PrimaryReplica\Commands\RunSql;
use SwagEssentials\PrimaryReplica\ConnectionDecision;
use SwagEssentials\PrimaryReplica\PdoFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Bridge implements SubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'Shopware_Console_Add_Command' => 'addConsoleCommands',
            'Enlight_Controller_Front_PreDispatch' => 'startDispatch',
            'Enlight_Controller_Front_DispatchLoopShutdown' => 'dispatchShutdown',
        ];
    }

    public function addConsoleCommands(): ArrayCollection
    {
        return new ArrayCollection(
            [
                new RunSql(),
            ]
        );
    }

    public function startDispatch(): void
    {
        if ($this->container->has('shop')) {
            if (!PdoFactory::$connectionDecision instanceof ConnectionDecision) {
                throw new \Exception('Connectiondecision not set');
            }

            PdoFactory::$connectionDecision->setPinnedTables(
                array_merge(
                    $this->container->get('session')->get('tables', []),
                    PdoFactory::$connectionDecision->getPinnedTables()
                )
            );

            return;
        }

        if ($this->container->has('backendsession')) {
            if (!PdoFactory::$connectionDecision instanceof ConnectionDecision) {
                throw new \Exception('Connectiondecision not set');
            }

            PdoFactory::$connectionDecision->setPinnedTables(
                array_merge(
                    $this->container->get('backendsession')->get('tables', []),
                    PdoFactory::$connectionDecision->getPinnedTables()
                )
            );
        }
    }

    public function dispatchShutdown(): void
    {
        if ($this->container->has('shop')) {
            $this->container->get('session')->offsetSet('tables', PdoFactory::$connectionDecision->getPinnedTables());

            return;
        }

        if ($this->container->has('backendsession')) {
            $this->container->get('backendsession')
                ->offsetSet('tables', PdoFactory::$connectionDecision->getPinnedTables());
        }
    }
}
