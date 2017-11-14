<?php declare(strict_types=1);

namespace SwagEssentials\PrimaryReplica\Subscriber;

use Enlight\Event\SubscriberInterface;
use Enlight_Components_Session_Namespace;
use SwagEssentials\PrimaryReplica\Commands\RunSql;
use Doctrine\Common\Collections\ArrayCollection;
use SwagEssentials\PrimaryReplica\ConnectionDecision;
use SwagEssentials\PrimaryReplica\PdoFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Bridge implements SubscriberInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var Enlight_Components_Session_Namespace
     */
    private $session;

    /**
     * @param ContainerInterface $container
     * @param Enlight_Components_Session_Namespace $session
     */
    public function __construct(ContainerInterface $container, Enlight_Components_Session_Namespace $session)
    {
        $this->container = $container;
        $this->session = $session;
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
                    $this->container->get('backendsession')->get('tables', []),
                    PdoFactory::$connectionDecision->getPinnedTables()
                )
            );
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
        }

        if ($this->container->has('backendsession')) {
            $this->container->get('backendsession')
                ->offsetSet('tables', PdoFactory::$connectionDecision->getPinnedTables());
        }
    }
}
