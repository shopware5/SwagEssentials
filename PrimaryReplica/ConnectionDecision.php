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

    public function __construct(\PDO $primaryConnection, ConnectionPool $replicaPool)
    {
        $this->primaryConnection = $primaryConnection;
        $this->replicaPool = $replicaPool;
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

        return (stripos($sql, 'SELECT') !== 0);
    }

    public function __destruct()
    {
        error_log(print_r($_SERVER['REQUEST_URI'], true)."\n", 3, Shopware()->DocPath() . '/../error.log');
        error_log(print_r($this->counter, true)."\n", 3, Shopware()->DocPath() . '/../error.log');
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
            '#(s_article|s_categorie|s_blog|s_core|s_user|s_library|s_emotion|s_plugin|s_order)[a-z0-9_]*#i',
            $sql,
            $matches
        );
        if ($number) {
            return array_unique($matches[0]);
        }

        return [];
    }
}
