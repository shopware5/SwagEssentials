<?php

namespace SwagEssentials\PrimaryReplica;

use Doctrine\Common\Util\Debug;

/**
 * Class ConnectionDecision returns the primary or a replica connection depending on the given query
 * Write queries and queries involving tables that have been written to before will get the primary connection
 * returned, everything else a random replica connection
 * @package SwagEssentials\PrimaryReplica
 */
class ConnectionDecision
{
    /**
     * @var \PDO
     */
    private $primaryConnection;
    /**
     * @var ConnectionPool
     */
    private $replicaPool;

    private $counter = [];

    private $pinnedTables = [
        's_core_sessions' => true
    ];

    /** @var \Zend_Cache_Core  */
    private $cache;

    public function __construct(\PDO $primaryConnection, ConnectionPool $replicaPool, \Zend_Cache_Core $cache)
    {
        $this->primaryConnection = $primaryConnection;
        $this->replicaPool = $replicaPool;

        $this->cache = $cache;
        $this->tables= $this->getTables();
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
            return $this->primaryConnection;
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
        $this->counter[$name] += 1;
    }

    
    private function isWriteQuery($sql)
    {
        $sql = trim($sql);

        return (stripos($sql, 'SELECT') !== 0 && stripos($sql, 'DESCRIBE') !== 0);
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
        if ($tables = $this->cache->load('primary_replica_tables')) {
            return $tables;
        }

        $tables = $this->primaryConnection->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);

        $result = [];
        foreach ($tables as $table) {
            $parts = explode('_', $table);
            $result[] = $parts[0] . '_' . $parts[1];
        }
        $tables = implode('|', array_map('preg_quote', array_unique($result)));

        $this->cache->save($tables, 'primary_replica_tables', [], 3600);

        return $tables;
    }
}
