<?php

namespace SwagEssentials\PrimaryReplica;

/**
 * Class ConnectionDecision returns the primary or a replica connection depending on the given query
 * Write queries and queries involving tables that have been written to before will get the primary connection
 * returned, everything else a random replica connection
 * @package SwagEssentials\PrimaryReplica
 */
class ConnectionDecision
{
    /**
     * @var ConnectionPool
     */
    private $replicaPool;

    private $counter = [];

    private $pinnedTables = [
        's_core_sessions' => true
    ];

    private $config;

    public function __construct(ConnectionPool $replicaPool, $config)
    {
        $this->replicaPool = $replicaPool;

        $this->tables = $this->getTables();
        $this->config = $config;
    }

    /**
     * Return a PDO connection for the given SQL query.
     * If e.g. a table has been written to before, the primary connection will be returned ("primary pinning")
     *
     * @param $sql
     * @return \PDO
     */
    public function getConnectionForQuery($sql)
    {
        $affected = $this->getAffectedTables($sql);     // tables in this query
        $isWriteQuery = $this->isWriteQuery($sql);         // is write query?
        $queryInvolvesPinnedTable = false;                   // is only write query because of prior write?
//        $isBasketQuery = stripos($sql, 's_order_basket') !== false;
//
//        if ($isBasketQuery && isset($this->config['basket_connection'])) {
//            $name = $this->config['basket_connection'];
//            $this->count($name, $sql);
//            return $this->replicaPool->getConnectionByName($name);
//        }

        if (!$isWriteQuery) {
            foreach ($affected as $table) {
                if (isset($this->pinnedTables[$table])) {
                    $queryInvolvesPinnedTable = true;
                }
            }
        }

        if ($isWriteQuery || $queryInvolvesPinnedTable) {
            $this->count('primary', $sql);
            if (!$queryInvolvesPinnedTable) {
                foreach ($affected as $table) {
                    $this->pinnedTables[$table] = $sql;
                }
            }
            
            return $this->replicaPool->getConnectionByName('primary');
        }

        list($name, $replica) = $this->replicaPool->getRandomConnection();

        $this->count($name, $sql);
        return $replica;
    }

    private function count($name, $query)
    {
        if (!isset($this->counter[$name])) {
            $this->counter[$name] = 0;
        }
        ++$this->counter[$name];
    }

    private function isWriteQuery($sql)
    {
        $sql = trim($sql);

        if (
            stripos($sql, 'START') === 0 ||
            stripos($sql, 'BEGIN') === 0 ||
            stripos($sql, 'ROLLBACK') === 0 ||
            stripos($sql, 'COMMIT') === 0
        ) {
            return true;
        }

        return (stripos($sql, 'SHOW') !== 0 && stripos($sql, 'SELECT') !== 0 && stripos($sql, 'DESCRIBE') !== 0) || stripos(
            $sql,
            'FOR UPDATE'
        ) !== false;
    }

    /**
     * @return array
     */
    public function getPinnedTables()
    {
        return $this->pinnedTables;
    }

    /**
     * @param array $pinnedTables
     */
    public function setPinnedTables($pinnedTables)
    {
        $this->pinnedTables = $pinnedTables;
    }


    /**
     * Will quickly find all tables from within the given query.
     * Is quite a rough heuristic, but is way faster than other approaches
     *
     * @param $sql
     * @return array
     */
    private function getAffectedTables($sql)
    {
        $matches = [];
        $number = preg_match_all(
            '#(' . $this->tables . ')[a-z0-9_]*#i',
            $sql,
            $matches
        );
        if ($number) {
            return array_unique($matches[0]);
        }

        return [];
    }

    /**
     * Gets a shortened and cached list of all database tables. It is safe to assume, that the cache will be cleared
     * after a structural change in the database
     *
     * @return string
     */
    private function getTables()
    {
        $apc_fetch = function_exists('apc_fetch') ? 'apc_fetch' : function_exists('apcu_fetch') ? 'apcu_fetch' : null;
        $apc_store = function_exists('apc_store') ? 'apc_fetch' : function_exists('apcu_store') ? 'apcu_store' : null;

        $apc_available = $apc_store && $apc_fetch;

        if ($apc_available && $tables = $apc_fetch('primary_replica_tables')) {
            return unserialize($tables);
        }

        $tables = $this->replicaPool->getRandomConnection()[1]->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);

        $result = [];
        foreach ($tables as $table) {
            $parts = explode('_', $table);
            $result[] = $parts[0] . '_' . $parts[1];
        }
        $tables = implode('|', array_map('preg_quote', array_unique($result)));

        if ($apc_available) {
            $apc_store('primary_replica_tables', serialize($tables));
        }

        return $tables;
    }
}
