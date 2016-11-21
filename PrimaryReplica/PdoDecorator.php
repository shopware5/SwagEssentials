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
    /**
     * @var PDO
     */
    private $primaryPdo;

    public function __construct(PDO $primaryPdo, ConnectionDecision $connectionDecision)
    {
        $this->connectionDecision = $connectionDecision;
        $this->primaryPdo = $primaryPdo;
    }


    #
    # Overrides of the original PDO object
    # in order to inspect the queries
    #

    public function prepare($statement, $options = array())
    {
        return $this->connectionDecision->getConnectionForQuery($statement)->prepare($statement, $options);
    }


    public function query()
    {
        // remove empty constructor params list if it exists
        $args = func_get_args();

        return call_user_func_array(array($this->connectionDecision->getConnectionForQuery($args[0]), 'query'), $args);
    }

    public function exec($statement)
    {
        return $this->connectionDecision->getConnectionForQuery($statement)->exec($statement);
    }

    #
    # Overrides of the original PDO object
    # in order to "decorate" it  - no inspection here
    #

    public function beginTransaction()
    {
        return $this->primaryPdo->beginTransaction();
    }

    public function commit()
    {
        return $this->primaryPdo->commit();
    }

    public function rollBack()
    {
        return $this->primaryPdo->rollBack();
    }

    public function inTransaction()
    {
        return $this->primaryPdo->inTransaction();
    }

    public function setAttribute($attribute, $value)
    {
        $this->primaryPdo->setAttribute($attribute, $value);
    }

    public function lastInsertId($name = null)
    {
        return $this->primaryPdo->lastInsertId($name);
    }

    public function errorCode()
    {
        return $this->primaryPdo->errorCode();
    }

    public function errorInfo()
    {
        return $this->primaryPdo->errorInfo();
    }

    public function getAttribute($attribute)
    {
        return $this->primaryPdo->getAttribute($attribute);
    }

    public function quote($string, $parameter_type = PDO::PARAM_STR)
    {
        return $this->primaryPdo->quote($string, $parameter_type);
    }

    public static function getAvailableDrivers()
    {
        parent::getAvailableDrivers();
    }
}
