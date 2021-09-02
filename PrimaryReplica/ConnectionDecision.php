<?php declare(strict_types=1);

namespace SwagEssentials\PrimaryReplica;

/**
 * Class ConnectionDecision returns the primary or a replica connection depending on the given query
 * Write queries and queries involving tables that have been written to before will get the primary connection
 * returned, everything else a random replica connection
 */
class ConnectionDecision
{
    protected static $DEBUG = false;

    /**
     * @var ConnectionPool
     */
    protected $replicaPool;

    protected $counter = [];

    protected $pinnedTables = [
        's_core_sessions' => true,
        's_core_sessions_backend' => true,
        's_order_basket' => true,
    ];

    protected $config;

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
        // get list of tables involved in the query
        $affected = $this->getAffectedTables($sql);

        // is the given query a write query which needs to go to the primary connection?
        $isWriteQuery = $this->isWriteQuery($sql);

        // does the query contain a table which has been written to before?
        $queryInvolvesPinnedTable = false;
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
                    $this->pinnedTables[$table] = self::$DEBUG ? $sql : true;
                }
            }

            return $this->replicaPool->getConnectionByName('primary');
        }

        list($name, $replica) = $this->replicaPool->getRandomConnection();

        $this->count($name, $sql);

        return $replica;
    }

    /**
     * Simple statistics: Which connection has been used how often?
     *
     * @param $name
     * @param $query
     */
    protected function count($name, $query)
    {
        if (!isset($this->counter[$name])) {
            $this->counter[$name] = 0;
        }
        ++$this->counter[$name];
    }

    /**
     * In debug mode: Print some debug information
     */
    public function __destruct()
    {
        if (!self::$DEBUG) {
            return;
        }

        error_log(print_r($this->counter, true));
    }

    /**
     * Determine, whether the given query is a write query or not
     *
     * @param $sql
     * @return bool
     */
    protected function isWriteQuery($sql): bool
    {
        $sql = trim($sql);

        // detect transaction related commands
        if (stripos($sql, 'START') === 0 ||
            stripos($sql, 'BEGIN') === 0 ||
            stripos($sql, 'ROLLBACK') === 0 ||
            stripos($sql, 'COMMIT') === 0
        ) {
            return true;
        }

        // FOR UPDATE clause requires primary connection as well
        if (stripos($sql, 'FOR UPDATE') !== false) {
            return true;
        }

        // if query does NOT start with common READ keywords,
        // assume WRITE connection as well
        if (stripos($sql, 'SHOW') !== 0 &&
            stripos($sql, 'SELECT') !== 0 &&
            stripos($sql, 'EXPLAIN') !== 0 &&
            stripos($sql, 'DESC') !== 0 &&
            stripos($sql, 'DESCRIBE') !== 0
        ) {
            return true;
        }


        // by now, only read queries should remain
        return false;
    }

    /**
     * @return array
     */
    public function getPinnedTables(): array
    {
        return $this->pinnedTables;
    }

    /**
     * @param array $pinnedTables
     */
    public function setPinnedTables(array $pinnedTables)
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
    protected function getAffectedTables($sql): array
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
    protected function getTables(): string
    {
        $apc_fetch = function_exists('apc_fetch') ? 'apc_fetch' : (function_exists('apcu_fetch') ? 'apcu_fetch' : null);
        $apc_store = function_exists('apc_store') ? 'apc_fetch' : (function_exists('apcu_store') ? 'apcu_store' : null);

        $apc_available = $apc_store && $apc_fetch;

        if ($apc_available && $tables = $apc_fetch('primary_replica_tables')) {
            return unserialize($tables, ['allowed_classes' => true]);
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
