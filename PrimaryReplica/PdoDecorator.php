<?php

namespace SwagEssentials\PrimaryReplica;

use PDO;

/**
 * Class PdoDecorator decorates a default PDO connection and will dispatch any query to either the primary or
 * the replica connections. The connection selection is done by the `ConnectionDecision` service
 * @package SwagEssentials\PrimaryReplica
 */
class PdoDecorator extends \PDO
{
    /**
     * @var ConnectionDecision
     */
    private $connectionDecision;

    private $lastConnection = null;
    /**
     * @var ConnectionPool
     */
    private $connectionPool;

    public function __construct(ConnectionDecision $connectionDecision, ConnectionPool $connectionPool)
    {
        $this->connectionDecision = $connectionDecision;
        $this->connectionPool = $connectionPool;

        $this->lastConnection = $connectionPool->getRandomConnection();
    }


    #
    # Overrides of the original PDO object
    # in order to inspect the queries
    #

    public function prepare($statement, $options = [])
    {
        $this->lastConnection = $this->connectionPool->getConnectionByName('primary');

        return $this->lastConnection->prepare($statement, $options);
    }


    public function query()
    {
        // remove empty constructor params list if it exists
        $args = func_get_args();

        $this->lastConnection = $this->connectionDecision->getConnectionForQuery($args[0]);

        return call_user_func_array([$this->lastConnection, 'query'], $args);
    }

    public function exec($statement)
    {
        $this->lastConnection = $this->connectionDecision->getConnectionForQuery($statement);

        return $this->lastConnection->exec($statement);
    }

    #
    # Overrides of the original PDO object
    # in order to "decorate" it  - no inspection here
    #

    public function beginTransaction()
    {
        return $this->connectionPool->getConnectionByName('primary')->beginTransaction();
    }

    public function commit()
    {
        return $this->connectionPool->getConnectionByName('primary')->commit();
    }

    public function rollBack()
    {
        return $this->connectionPool->getConnectionByName('primary')->rollBack();
    }

    public function inTransaction()
    {
        return $this->connectionPool->getConnectionByName('primary')->inTransaction();
    }

    public function setAttribute($attribute, $value)
    {
        $this->connectionPool->getConnectionByName('primary')->setAttribute($attribute, $value);
    }

    public function lastInsertId($name = null)
    {
        return $this->connectionPool->getConnectionByName('primary')->lastInsertId($name);
    }

    public function errorCode()
    {
        return $this->lastConnection->errorCode();
    }

    public function errorInfo()
    {
        return $this->lastConnection->errorInfo();
    }

    public function getAttribute($attribute)
    {
        return $this->connectionPool->getRandomConnection()[1]->getAttribute($attribute);
    }

    public function quote($string, $parameter_type = PDO::PARAM_STR)
    {
        return $this->connectionPool->getRandomConnection()[1]->quote($string, $parameter_type);
    }
}
