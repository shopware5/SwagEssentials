<?php

declare(strict_types=1);

namespace SwagEssentials\PrimaryReplica;

use PDO;

if (PHP_VERSION_ID >= 80000) {
    /**
     * Class PdoDecorator decorates a default PDO connection and will dispatch any query to either the primary or
     * the replica connections. The connection selection is done by the `ConnectionDecision` service
     */
    class PdoDecorator extends \PDO
    {
        /**
         * @var ConnectionDecision
         */
        protected $connectionDecision;

        /**
         * @var \PDO
         */
        protected $lastConnection;

        /**
         * @var ConnectionPool
         */
        protected $connectionPool;

        public function __construct(ConnectionDecision $connectionDecision, ConnectionPool $connectionPool)
        {
            $this->connectionDecision = $connectionDecision;
            $this->connectionPool = $connectionPool;

            $this->lastConnection = $connectionPool->getRandomConnection()[1];
        }

        /**
         * Overrides of the original PDO object
         * in order to inspect the queries
         *
         * @param string                $statement
         * @param array<\PDO::*, mixed> $options
         */
        public function prepare($statement, $options = [])
        {
            $this->lastConnection = $this->connectionDecision->getConnectionForQuery($statement);

            return $this->lastConnection->prepare($statement, $options);
        }

        public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs)
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

        //
        // Overrides of the original PDO object
        // in order to "decorate" it  - no inspection here
        //
        public function beginTransaction()
        {
            if (!$this->inTransaction()) {
                return $this->connectionPool->getConnectionByName('primary')->beginTransaction();
            }

            return true;
        }

        public function commit()
        {
            if ($this->inTransaction()) {
                return $this->connectionPool->getConnectionByName('primary')->commit();
            }

            return false;
        }

        public function rollBack()
        {
            if ($this->inTransaction()) {
                return $this->connectionPool->getConnectionByName('primary')->rollBack();
            }

            return false;
        }

        public function inTransaction()
        {
            return $this->connectionPool->getConnectionByName('primary')->inTransaction();
        }

        public function setAttribute($attribute, $value)
        {
            $this->connectionPool->setAttribute($attribute, $value);

            $results = [];
            foreach ($this->connectionPool->getActiveConnections() as $connection) {
                $results[] = $connection->setAttribute($attribute, $value);
            }

            return !in_array(false, $results);
        }

        public function lastInsertId($name = null)
        {
            return $this->connectionPool->getConnectionByName('primary')->lastInsertId($name);
        }

        public function errorCode()
        {
            if (!$this->lastConnection instanceof \PDO) {
                throw new \Exception('No last connection existing');
            }

            return $this->lastConnection->errorCode();
        }

        /**
         * @return array<int, string>
         */
        public function errorInfo()
        {
            if (!$this->lastConnection instanceof \PDO) {
                throw new \Exception('No last connection existing');
            }

            return $this->lastConnection->errorInfo();
        }

        public function getAttribute($attribute)
        {
            return $this->connectionPool->getRandomConnection()[1]->getAttribute($attribute);
        }

        public function quote($string, $parameter_type = PDO::PARAM_STR)
        {
            return $this->connectionPool->getRandomConnection()[1]->quote((string) $string, (int) $parameter_type);
        }
    }
} else {
    /**
     * Class PdoDecorator decorates a default PDO connection and will dispatch any query to either the primary or
     * the replica connections. The connection selection is done by the `ConnectionDecision` service
     */
    class PdoDecorator extends \PDO
    {
        /**
         * @var ConnectionDecision
         */
        protected $connectionDecision;

        /**
         * @var \PDO
         */
        protected $lastConnection;

        /**
         * @var ConnectionPool
         */
        protected $connectionPool;

        public function __construct(ConnectionDecision $connectionDecision, ConnectionPool $connectionPool)
        {
            $this->connectionDecision = $connectionDecision;
            $this->connectionPool = $connectionPool;

            $this->lastConnection = $connectionPool->getRandomConnection()[1];
        }

        /**
         * Overrides of the original PDO object
         * in order to inspect the queries
         *
         * @param string                $statement
         * @param array<\PDO::*, mixed> $options
         */
        public function prepare($statement, $options = [])
        {
            $this->lastConnection = $this->connectionDecision->getConnectionForQuery($statement);

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

        //
        // Overrides of the original PDO object
        // in order to "decorate" it  - no inspection here
        //
        public function beginTransaction()
        {
            if (!$this->inTransaction()) {
                return $this->connectionPool->getConnectionByName('primary')->beginTransaction();
            }

            return true;
        }

        public function commit()
        {
            if ($this->inTransaction()) {
                return $this->connectionPool->getConnectionByName('primary')->commit();
            }

            return false;
        }

        public function rollBack()
        {
            if ($this->inTransaction()) {
                return $this->connectionPool->getConnectionByName('primary')->rollBack();
            }

            return false;
        }

        public function inTransaction()
        {
            return $this->connectionPool->getConnectionByName('primary')->inTransaction();
        }

        public function setAttribute($attribute, $value)
        {
            $this->connectionPool->setAttribute($attribute, $value);

            $results = [];
            foreach ($this->connectionPool->getActiveConnections() as $connection) {
                $results[] = $connection->setAttribute($attribute, $value);
            }

            return !in_array(false, $results);
        }

        public function lastInsertId($name = null)
        {
            return $this->connectionPool->getConnectionByName('primary')->lastInsertId($name);
        }

        public function errorCode()
        {
            if (!$this->lastConnection instanceof \PDO) {
                throw new \Exception('No last connection existing');
            }

            return $this->lastConnection->errorCode();
        }

        /**
         * @return array<int, string>
         */
        public function errorInfo()
        {
            if (!$this->lastConnection instanceof \PDO) {
                throw new \Exception('No last connection existing');
            }

            return $this->lastConnection->errorInfo();
        }

        public function getAttribute($attribute)
        {
            return $this->connectionPool->getRandomConnection()[1]->getAttribute($attribute);
        }

        public function quote($string, $parameter_type = PDO::PARAM_STR)
        {
            return $this->connectionPool->getRandomConnection()[1]->quote((string) $string, (int) $parameter_type);
        }
    }
}
