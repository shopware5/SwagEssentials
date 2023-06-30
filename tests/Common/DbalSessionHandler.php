<?php

declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagEssentials\Tests\Common;

use Doctrine\DBAL\Connection;

/**
 * Session handler using a PDO connection to read and write data.
 *
 * It works with MySQL, PostgreSQL, Oracle, SQL Server and SQLite and implements
 * different locking strategies to handle concurrent access to the same session.
 * Locking is necessary to prevent loss of data due to race conditions and to keep
 * the session data consistent between read() and write(). With locking, requests
 * for the same session will wait until the other one finished writing. For this
 * reason it's best practice to close a session as early as possible to improve
 * concurrency. PHPs internal files session handler also implements locking.
 *
 * Attention: Since SQLite does not support row level locks but locks the whole database,
 * it means only one session can be accessed at a time. Even different sessions would wait
 * for another to finish. So saving session in SQLite should only be considered for
 * development or prototypes.
 *
 * Session data is a binary string that can contain non-printable characters like the null byte.
 * For this reason it must be saved in a binary column in the database like BLOB in MySQL.
 * Saving it in a character column could corrupt the data. You can use createTable()
 * to initialize a correctly defined table.
 *
 * @see http://php.net/sessionhandlerinterface
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Michael Williams <michael.williams@funsational.com>
 * @author Tobias Schultze <http://tobion.de>
 */
class DbalSessionHandler implements \SessionHandlerInterface
{
    /**
     * No locking is done. This means sessions are prone to loss of data due to
     * race conditions of concurrent requests to the same session. The last session
     * write will win in this case. It might be useful when you implement your own
     * logic to deal with this like an optimistic approach.
     */
    public const LOCK_NONE = 0;

    /**
     * Creates an application-level lock on a session. The disadvantage is that the
     * lock is not enforced by the database and thus other, unaware parts of the
     * application could still concurrently modify the session. The advantage is it
     * does not require a transaction.
     * This mode is not available for SQLite and not yet implemented for oci and sqlsrv.
     */
    public const LOCK_ADVISORY = 1;

    /**
     * Issues a real row lock. Since it uses a transaction between opening and
     * closing a session, you have to be careful when you use same database connection
     * that you also use for your application logic. This mode is the default because
     * it's the only reliable solution across DBMSs.
     */
    public const LOCK_TRANSACTIONAL = 2;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var string|false|null DSN string or null for session.save_path or false when lazy connection disabled
     */
    protected $dsn = false;

    /**
     * @var string Table name
     */
    protected $table = 'sessions';

    /**
     * @var string Column for session id
     */
    protected $idCol = 'sess_id';

    /**
     * @var string Column for session data
     */
    protected $dataCol = 'sess_data';

    /**
     * @var string Column for lifetime
     */
    protected $expiryCol = 'sess_expiry';

    /**
     * @var string Column for timestamp
     */
    protected $timeCol = 'sess_time';

    /**
     * @var string Username when lazy-connect
     */
    protected $username = '';

    /**
     * @var string Password when lazy-connect
     */
    protected $password = '';

    /**
     * @var array Connection options when lazy-connect
     */
    protected $connectionOptions = [];

    /**
     * @var int The strategy for locking, see constants
     */
    protected $lockMode = self::LOCK_TRANSACTIONAL;

    /**
     * It's an array to support multiple reads before closing which is manual, non-standard usage.
     *
     * @var \PDOStatement[] An array of statements to release advisory locks
     */
    protected $unlockStatements = [];

    /**
     * @var bool True when the current session exists but expired according to session.gc_maxlifetime
     */
    protected $sessionExpired = false;

    /**
     * @var bool Whether a transaction is active
     */
    protected $inTransaction = false;

    /**
     * @var bool Whether gc() has been called
     */
    protected $gcCalled = false;

    /**
     * Constructor.
     *
     * You can either pass an existing database connection as PDO instance or
     * pass a DSN string that will be used to lazy-connect to the database
     * when the session is actually used. Furthermore it's possible to pass null
     * which will then use the session.save_path ini setting as PDO DSN parameter.
     *
     * List of available options:
     *  * db_table: The name of the table [default: sessions]
     *  * db_id_col: The column where to store the session id [default: sess_id]
     *  * db_data_col: The column where to store the session data [default: sess_data]
     *  * db_expiry_col: The column where to store the expirytime [default: sess_expiry]
     *  * db_time_col: The column where to store the timestamp [default: sess_time]
     *  * db_username: The username when lazy-connect [default: '']
     *  * db_password: The password when lazy-connect [default: '']
     *  * db_connection_options: An array of driver-specific connection options [default: array()]
     *  * lock_mode: The strategy for locking, see constants [default: LOCK_TRANSACTIONAL]
     *
     * @param Connection $connection A \PDO instance or DSN string or null
     * @param array      $options    An associative array of options
     */
    public function __construct(Connection $connection, array $options = [])
    {
        $this->connection = $connection;

        $this->table = isset($options['db_table']) ? $options['db_table'] : $this->table;
        $this->idCol = isset($options['db_id_col']) ? $options['db_id_col'] : $this->idCol;
        $this->dataCol = isset($options['db_data_col']) ? $options['db_data_col'] : $this->dataCol;
        $this->expiryCol = isset($options['db_expiry_col']) ? $options['db_expiry_col'] : $this->expiryCol;
        $this->timeCol = isset($options['db_time_col']) ? $options['db_time_col'] : $this->timeCol;
        $this->username = isset($options['db_username']) ? $options['db_username'] : $this->username;
        $this->password = isset($options['db_password']) ? $options['db_password'] : $this->password;
        $this->connectionOptions = isset($options['db_connection_options']) ? $options['db_connection_options'] : $this->connectionOptions;
        $this->lockMode = isset($options['lock_mode']) ? $options['lock_mode'] : $this->lockMode;
    }

    /**
     * Creates the table to store sessions which can be called once for setup.
     *
     * Session ID is saved in a column of maximum length 128 because that is enough even
     * for a 512 bit configured session.hash_function like Whirlpool. Session data is
     * saved in a BLOB. One could also use a shorter inlined varbinary column
     * if one was sure the data fits into it.
     *
     * @throws \PDOException    When the table already exists
     * @throws \DomainException When an unsupported PDO driver is used
     */
    public function createTable()
    {
        // connect if we are not yet
        $this->getConnection();

        $sql = "CREATE TABLE $this->table ($this->idCol VARBINARY(128) NOT NULL PRIMARY KEY, $this->dataCol BLOB NOT NULL, $this->expiryCol MEDIUMINT NOT NULL, $this->timeCol INTEGER UNSIGNED NOT NULL) COLLATE utf8_bin, ENGINE = InnoDB";

        try {
            $this->connection->exec($sql);
        } catch (\PDOException $e) {
            $this->rollback();

            throw $e;
        }
    }

    /**
     * Returns true when the current session exists but expired according to session.gc_maxlifetime.
     *
     * Can be used to distinguish between a new session and one that expired due to inactivity.
     *
     * @return bool Whether current session expired
     */
    public function isSessionExpired()
    {
        return $this->sessionExpired;
    }

    public function open($savePath, $sessionName)
    {
        return true;
    }

    public function read($sessionId)
    {
        try {
            return $this->doRead($sessionId);
        } catch (\PDOException $e) {
            $this->rollback();

            throw $e;
        }
    }

    public function gc($maxlifetime)
    {
        // We delay gc() to close() so that it is executed outside the transactional and blocking read-write process.
        // This way, pruning expired sessions does not block them from being started while the current session is used.
        $this->gcCalled = true;

        return true;
    }

    public function destroy($sessionId)
    {
        // delete the record associated with this id
        $sql = "DELETE FROM $this->table WHERE $this->idCol = :id";

        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(':id', $sessionId, \PDO::PARAM_STR);
            $stmt->execute();
        } catch (\PDOException $e) {
            $this->rollback();

            throw $e;
        }

        return true;
    }

    public function write($sessionId, $data)
    {
        $maxlifetime = (int) ini_get('session.gc_maxlifetime');

        try {
            // We use a single MERGE SQL query when supported by the database.
            $mergeStmt = $this->getMergeStatement($sessionId, $data, $maxlifetime);
            if ($mergeStmt !== null) {
                $mergeStmt->execute();

                return true;
            }

            $updateStmt = $this->connection->prepare(
                "UPDATE $this->table SET $this->dataCol = :data, $this->expiryCol = :expiry, $this->timeCol = :time WHERE $this->idCol = :id"
            );
            $updateStmt->bindParam(':id', $sessionId, \PDO::PARAM_STR);
            $updateStmt->bindParam(':data', $data, \PDO::PARAM_LOB);
            $updateStmt->bindValue(':expiry', time() + $maxlifetime, \PDO::PARAM_INT);
            $updateStmt->bindValue(':time', time(), \PDO::PARAM_INT);
            $updateStmt->execute();

            // When MERGE is not supported, like in Postgres < 9.5, we have to use this approach that can result in
            // duplicate key errors when the same session is written simultaneously (given the LOCK_NONE behavior).
            // We can just catch such an error and re-execute the update. This is similar to a serializable
            // transaction with retry logic on serialization failures but without the overhead and without possible
            // false positives due to longer gap locking.
            if (!$updateStmt->rowCount()) {
                try {
                    $insertStmt = $this->connection->prepare(
                        "INSERT INTO $this->table ($this->idCol, $this->dataCol, $this->expiryCol, $this->timeCol) VALUES (:id, :data, :expiry, :time)"
                    );
                    $insertStmt->bindParam(':id', $sessionId, \PDO::PARAM_STR);
                    $insertStmt->bindParam(':data', $data, \PDO::PARAM_LOB);
                    $insertStmt->bindValue(':expiry', time() + $maxlifetime, \PDO::PARAM_INT);
                    $insertStmt->bindValue(':time', time(), \PDO::PARAM_INT);
                    $insertStmt->execute();
                } catch (\PDOException $e) {
                    // Handle integrity violation SQLSTATE 23000 (or a subclass like 23505 in Postgres) for duplicate keys
                    if (strpos($e->getCode(), '23') === 0) {
                        $updateStmt->execute();
                    } else {
                        throw $e;
                    }
                }
            }
        } catch (\PDOException $e) {
            $this->rollback();

            throw $e;
        }

        return true;
    }

    public function close()
    {
        $this->commit();

        while ($unlockStmt = array_shift($this->unlockStatements)) {
            $unlockStmt->execute();
        }

        if ($this->gcCalled) {
            $this->gcCalled = false;

            // delete the session records that have expired
            $sql = "DELETE FROM $this->table WHERE $this->expiryCol < :time";

            $stmt = $this->connection->prepare($sql);
            $stmt->bindValue(':time', time(), \PDO::PARAM_INT);
            $stmt->execute();
        }

        if ($this->dsn !== false) {
            $this->connection = null; // only close lazy-connection
        }

        return true;
    }

    /**
     * Return a PDO instance.
     *
     * @return Connection
     */
    protected function getConnection()
    {
        if ($this->connection === null) {
            $this->connect($this->dsn ?: ini_get('session.save_path'));
        }

        return $this->connection;
    }

    /**
     * Lazy-connects to the database.
     *
     * @param string $dsn DSN string
     */
    protected function connect($dsn)
    {
        throw new \Exception('Should not get called');
    }

    /**
     * Helper method to begin a transaction.
     *
     * Since SQLite does not support row level locks, we have to acquire a reserved lock
     * on the database immediately. Because of https://bugs.php.net/42766 we have to create
     * such a transaction manually which also means we cannot use PDO::commit or
     * PDO::rollback or PDO::inTransaction for SQLite.
     *
     * Also MySQLs default isolation, REPEATABLE READ, causes deadlock for different sessions
     * due to http://www.mysqlperformanceblog.com/2013/12/12/one-more-innodb-gap-lock-to-avoid/ .
     * So we change it to READ COMMITTED.
     */
    protected function beginTransaction()
    {
        if (!$this->inTransaction) {
            $this->connection->exec('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
            $this->connection->beginTransaction();
            $this->inTransaction = true;
        }
    }

    /**
     * Helper method to commit a transaction.
     */
    protected function commit()
    {
        if ($this->inTransaction) {
            try {
                $this->connection->commit();
                $this->inTransaction = false;
            } catch (\PDOException $e) {
                $this->rollback();

                throw $e;
            }
        }
    }

    /**
     * Helper method to rollback a transaction.
     */
    protected function rollback()
    {
        // We only need to rollback if we are in a transaction. Otherwise the resulting
        // error would hide the real problem why rollback was called. We might not be
        // in a transaction when not using the transactional locking behavior or when
        // two callbacks (e.g. destroy and write) are invoked that both fail.
        if ($this->inTransaction) {
            $this->connection->rollBack();
            $this->inTransaction = false;
        }
    }

    /**
     * Reads the session data in respect to the different locking strategies.
     *
     * We need to make sure we do not return session data that is already considered garbage according
     * to the session.gc_maxlifetime setting because gc() is called after read() and only sometimes.
     *
     * @param string $sessionId Session ID
     *
     * @return string The session data
     */
    protected function doRead($sessionId)
    {
        $this->sessionExpired = false;

        if ($this->lockMode === self::LOCK_ADVISORY) {
            $this->unlockStatements[] = $this->doAdvisoryLock($sessionId);
        }

        $selectSql = $this->getSelectSql();
        $selectStmt = $this->connection->prepare($selectSql);
        $selectStmt->bindParam(':id', $sessionId, \PDO::PARAM_STR);

        while (true) {
            $selectStmt->execute();
            $sessionRows = $selectStmt->fetchAll(\PDO::FETCH_NUM);

            if ($sessionRows) {
                if ($sessionRows[0][1] < time()) {
                    $this->sessionExpired = true;

                    return '';
                }

                return is_resource($sessionRows[0][0]) ? stream_get_contents($sessionRows[0][0]) : $sessionRows[0][0];
            }

            if ($this->lockMode === self::LOCK_TRANSACTIONAL) {
                // Exclusive-reading of non-existent rows does not block, so we need to do an insert to block
                // until other connections to the session are committed.
                try {
                    $insertStmt = $this->connection->prepare(
                        "INSERT INTO $this->table ($this->idCol, $this->dataCol, $this->expiryCol, $this->timeCol) VALUES (:id, :data, :expiry, :time)"
                    );
                    $insertStmt->bindParam(':id', $sessionId, \PDO::PARAM_STR);
                    $insertStmt->bindValue(':data', '', \PDO::PARAM_LOB);
                    $insertStmt->bindValue(':expiry', 0, \PDO::PARAM_INT);
                    $insertStmt->bindValue(':time', time(), \PDO::PARAM_INT);
                    $insertStmt->execute();
                } catch (\PDOException $e) {
                    // Catch duplicate key error because other connection created the session already.
                    // It would only not be the case when the other connection destroyed the session.
                    if (strpos($e->getCode(), '23') === 0) {
                        // Retrieve finished session data written by concurrent connection by restarting the loop.
                        // We have to start a new transaction as a failed query will mark the current transaction as
                        // aborted in PostgreSQL and disallow further queries within it.
                        $this->rollback();
                        $this->beginTransaction();

                        continue;
                    }

                    throw $e;
                }
            }

            return '';
        }
    }

    /**
     * Executes an application-level lock on the database.
     *
     * @param string $sessionId Session ID
     *
     * @throws \DomainException When an unsupported PDO driver is used
     *
     * @return \Doctrine\DBAL\Driver\Statement The statement that needs to be executed later to release the lock
     */
    protected function doAdvisoryLock($sessionId)
    {
        // should we handle the return value? 0 on timeout, null on error
        // we use a timeout of 50 seconds which is also the default for innodb_lock_wait_timeout
        $stmt = $this->connection->prepare('SELECT GET_LOCK(:key, 50)');
        $stmt->bindValue(':key', $sessionId, \PDO::PARAM_STR);
        $stmt->execute();

        $releaseStmt = $this->connection->prepare('DO RELEASE_LOCK(:key)');
        $releaseStmt->bindValue(':key', $sessionId, \PDO::PARAM_STR);

        return $releaseStmt;
    }

    /**
     * Return a locking or nonlocking SQL query to read session information.
     *
     * @throws \DomainException When an unsupported PDO driver is used
     *
     * @return string The SQL string
     */
    protected function getSelectSql()
    {
        if ($this->lockMode === self::LOCK_TRANSACTIONAL) {
            $this->beginTransaction();

            return "SELECT $this->dataCol, $this->expiryCol, $this->timeCol FROM $this->table WHERE $this->idCol = :id FOR UPDATE";
        }

        return "SELECT $this->dataCol, $this->expiryCol, $this->timeCol FROM $this->table WHERE $this->idCol = :id";
    }

    /**
     * Returns a merge/upsert (i.e. insert or update) statement when supported by the database for writing session data.
     *
     * @param string $sessionId   Session ID
     * @param string $data        Encoded session data
     * @param int    $maxlifetime session.gc_maxlifetime
     *
     * @return \Doctrine\DBAL\Driver\Statement The merge statement or null when not supported
     */
    protected function getMergeStatement($sessionId, $data, $maxlifetime)
    {
        $mergeSql = null;
        $mergeSql = "INSERT INTO $this->table ($this->idCol, $this->dataCol, $this->expiryCol, $this->timeCol) VALUES (:id, :data, :expiry, :time) " .
            "ON DUPLICATE KEY UPDATE $this->dataCol = VALUES($this->dataCol), $this->expiryCol = VALUES($this->expiryCol), $this->timeCol = VALUES($this->timeCol)";

        $mergeStmt = $this->connection->prepare($mergeSql);

        $mergeStmt->bindParam(':id', $sessionId, \PDO::PARAM_STR);
        $mergeStmt->bindParam(':data', $data, \PDO::PARAM_LOB);
        $mergeStmt->bindValue(':expiry', time() + $maxlifetime, \PDO::PARAM_INT);
        $mergeStmt->bindValue(':time', time(), \PDO::PARAM_INT);

        return $mergeStmt;
    }
}
