<?php

namespace SwagEssentials\PrimaryReplica;
use Doctrine\Common\Collections\ArrayCollection;
use SwagEssentials\PrimaryReplica\Commands\RunSql;

/**
 * Class ShopwareBridge separates the Shopware related logic (e.g. events) from the rest of the PDO / decoration / query
 * splitting related logic
 * @package SwagEssentials\PrimaryReplica
 */
class ShopwareBridge
{
    /**
     * @var \PDO
     */
    private $connection;

    /**
     * @var \Shopware\Components\DependencyInjection\Container
     */
    private $container;

    /**
     * ShopwareBridge constructor.
     * @param \PDO|\Doctrine\DBAL\Driver\Connection $connection
     */
    public function __construct(\PDO $connection)
    {
        $this->connection = $connection;
        $this->container = Shopware()->Container();

        $this->registerEvents();
    }

    /**
     * Register events needed for the DIC / Commands
     */
    private function registerEvents()
    {
        Shopware()->Events()->addListener(
            'Shopware_Console_Add_Command',
            [$this, 'addConsoleCommands']
        );

        // Container
        $resource = 'Enlight_Bootstrap_InitResource_';

        Shopware()->Events()->addListener(
            $resource . 'primaryreplica.decision',
            [$this, 'createDecision']
        );
        Shopware()->Events()->addListener(
            $resource . 'primaryreplica.connection_pool',
            [$this, 'createConnectionPool']
        );
        Shopware()->Events()->addListener(
            $resource . 'primaryreplica.pdo_decorator',
            [$this, 'createPdoDecorator']
        );
    }

    /**
     * Register the events needed for session pinning
     */
    public function initSessionPinning()
    {
        // Connection pinning
        Shopware()->Events()->addListener(
            'Enlight_Controller_Front_PreDispatch',
            [$this, 'startDispatch']
        );
        Shopware()->Events()->addListener(
            'Enlight_Controller_Front_DispatchLoopShutdown',
            [$this, 'dispatchShutdown']
        );

    }

    public function addConsoleCommands()
    {
        return new ArrayCollection([
            new RunSql()
        ]);
    }

    public function startDispatch()
    {
        /** @var ConnectionDecision $decision */
        $decision = $this->container->get('primaryreplica.decision');

        $decision->setPinnedTables(array_merge(Shopware()->Session()->get('tables'), $decision->getPinnedTables()));
    }

    public function dispatchShutdown()
    {
        /** @var ConnectionDecision $decision */
        $decision = $this->container->get('primaryreplica.decision');
        Shopware()->Session()->offsetSet('tables', $decision->getPinnedTables());
    }


    public function createConnectionPool()
    {
        return new ConnectionPool(
            $this->container->getParameter('shopware.db.replicas'),
            $this->connection,
            $this->container->hasParameter('shopware.db.includePrimary') ? $this->container->getParameter('shopware.db.includePrimary') : false,
            $this->container->hasParameter('shopware.db.stickyConnection') ? $this->container->getParameter('shopware.db.stickyConnection') : true
        );
    }

    public function createPdoDecorator()
    {
        return new PdoDecorator(
            $this->connection,
            $this->container->get('primaryreplica.decision')
        );
    }

    public function createDecision()
    {
        return new ConnectionDecision(
            $this->connection,
            $this->container->get('primaryreplica.connection_pool')
        );
    }

}
