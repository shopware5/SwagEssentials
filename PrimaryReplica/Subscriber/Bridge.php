<?php declare(strict_types=1);

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

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
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
                new RunSql(),
            ]
        );
    }

    public function startDispatch()
    {
        if ($this->container->has('shop')) {
            PdoFactory::$connectionDecision->setPinnedTables(
                array_merge(
                    $this->container->get('session')->get('tables', []),
                    PdoFactory::$connectionDecision->getPinnedTables()
                )
            );

            return;
        }

        if ($this->container->has('backendsession')) {
            PdoFactory::$connectionDecision->setPinnedTables(
                array_merge(
                    $this->container->get('backendsession')->get('tables', []),
                    PdoFactory::$connectionDecision->getPinnedTables()
                )
            );
        }
    }

    public function dispatchShutdown()
    {
        if ($this->container->has('shop')) {
            /** @var ConnectionDecision $decision */
            $this->container->get('session')
                ->offsetSet('tables', PdoFactory::$connectionDecision->getPinnedTables());

            return;
        }

        if ($this->container->has('backendsession')) {
            $this->container->get('backendsession')
                ->offsetSet('tables', PdoFactory::$connectionDecision->getPinnedTables());
        }
    }
}
