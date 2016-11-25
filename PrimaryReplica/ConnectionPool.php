<?php

namespace SwagEssentials\PrimaryReplica;

/**
 * Class ConnectionPool initializes and maintains all replica connection. Replica connections are created using the
 * primary connection as a template
 * @package SwagEssentials\PrimaryReplica
 */
class ConnectionPool
{
    private $replicaConfig;
    private $replicaConnections = [];

    private $attributes = [
        \PDO::ATTR_AUTOCOMMIT => null,
        \PDO::ATTR_CASE => null,
        \PDO::ATTR_CLIENT_VERSION => null,
        \PDO::ATTR_CONNECTION_STATUS => null,
        \PDO::ATTR_DRIVER_NAME => null,
        \PDO::ATTR_ERRMODE => null,
        \PDO::ATTR_ORACLE_NULLS => null,
        \PDO::ATTR_PERSISTENT => null,
//            \PDO::ATTR_PREFETCH => null,
        \PDO::ATTR_SERVER_INFO => null,
        \PDO::ATTR_SERVER_VERSION => null,
//            \PDO::ATTR_TIMEOUT => null
    ];

    /**
     * @var \PDO
     */
    private $primaryConnection;
    /**
     * @var bool
     */
    private $includePrimary;
    private $doStickToConnection = false;
    private $stickyConnectionName;

    public function __construct($replicaConfig, \PDO $primaryConnection, $includePrimary, $doSticktoConnection)
    {
        $this->replicaConfig = $replicaConfig;
        $this->primaryConnection = $primaryConnection;
        $this->includePrimary = $includePrimary;
        $this->doStickToConnection = $doSticktoConnection;

        $this->createAttributeTemplate();
    }

    /**
     * Return a specific connection from the pool
     *
     * @param $name
     * @return mixed
     */
    public function getConnectionByName($name)
    {
        if ($name == 'primary') {
            return $this->primaryConnection;
        }

        if (!isset($this->replicaConfig[$name])) {
            throw new \RuntimeException("Connection '$name' not found");
        }

        if (!isset($this->replicaConnections[$name])) {
            $this->buildConnection($name);
        }

        return $this->replicaConnections[$name];
    }

    /**
     * Return a random connection from the pool
     * if `includePrimary` is set in the `config.php` this might also include the primary connection
     *
     * @return array
     */
    public function getRandomConnection()
    {
        if ($this->includePrimary) {
            $connectionNames = array_merge(array_keys($this->replicaConfig), ['primary']);
        } else {
            $connectionNames = array_keys($this->replicaConfig);
        }

        if ($this->doStickToConnection && $this->stickyConnectionName) {
            return [$this->stickyConnectionName, $this->getConnectionByName($this->stickyConnectionName)];
        }

        $id = array_rand($connectionNames);

        if (null === $id) {
            throw new \RuntimeException('No connection found');
        }
        $name = $connectionNames[$id];

        if ($this->doStickToConnection) {
            $this->stickyConnectionName = $name;
        }

        return [$name, $this->getConnectionByName($name)];
    }

    /**
     * Create a connection to a replica server
     *
     * @param $name
     */
    private function buildConnection($name)
    {
        $replicaConfig = $this->replicaConfig[$name];

        $dsn = "mysql:dbname={$replicaConfig['dbname']};host={$replicaConfig['host']};port={$replicaConfig['port']};charset=utf8";
        $this->replicaConnections[$name] = new \PDO(
            $dsn, $replicaConfig['username'], $replicaConfig['password'], $this->attributes
        );
        $this->replicaConnections[$name]->exec("SET @@session.sql_mode = ''");
    }

    /**
     * Create a list of PDO attributes based on the primary connection
     */
    private function createAttributeTemplate()
    {
        foreach ($this->attributes as $attribute => $value) {
            $this->attributes[$attribute] = $this->primaryConnection->getAttribute($attribute);
        }
    }
}
