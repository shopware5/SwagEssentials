<?php declare(strict_types=1);

namespace SwagEssentials\PrimaryReplica;

/**
 * Class ConnectionPool initializes and maintains all replica connection. Replica connections are created using the
 * primary connection as a template
 */
class ConnectionPool
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var \PDO[]
     */
    private $connections = [];

    /**
     * @var bool
     */
    private $includePrimary;

    /**
     * @var bool
     */
    private $doStickToConnection;

    /**
     * @var array
     */
    private $weightedConnections;

    /**
     * @var string
     */
    private $stickyConnectionName;

    /**
     * @param array $config
     * @param $includePrimary
     * @param $doStickToConnection
     */
    public function __construct(array $config, $includePrimary, $doStickToConnection)
    {
        $this->config = $config;
        $this->includePrimary = $includePrimary;
        $this->doStickToConnection = $doStickToConnection;

        $this->prepareWeightedConnections();
    }

    /**
     * Return a specific connection from the pool
     *
     * @param $name
     * @return mixed
     */
    public function getConnectionByName($name)
    {
        if ($name !== 'primary' && !isset($this->config['replicas'][$name])) {
            throw new \RuntimeException("Connection '$name' not found");
        }

        return $this->getPDOConnection($name);
    }

    /**
     * Return a random connection from the pool
     * if `includePrimary` is set in the `config.php` this might also include the primary connection
     *
     * @return array
     */
    public function getRandomConnection(): array
    {
        if ($this->doStickToConnection && $this->stickyConnectionName) {
            return [$this->stickyConnectionName, $this->getConnectionByName($this->stickyConnectionName)];
        }

        $name = $this->getWeightedRandomConnection();

        if (null === $name) {
            throw new \RuntimeException('No connection found');
        }

        if ($this->doStickToConnection) {
            $this->stickyConnectionName = $name;
        }

        return [$name, $this->getConnectionByName($name)];
    }

    /**
     * Return a random connection based on the configured weights. E.g. a connection with weight "10" is more likely
     * to be returned than a connection with weight "1"
     *
     * @return null|string
     */
    private function getWeightedRandomConnection()
    {
        $weightedConnections = $this->weightedConnections;

        $rand = random_int(1, (int) array_sum($weightedConnections));

        foreach ($weightedConnections as $name => $weight) {
            $rand -= $weight;
            if ($rand <= 0) {
                return $name;
            }
        }
    }

    /**
     * Build an array of all available database connections and it weight
     */
    private function prepareWeightedConnections()
    {
        foreach ($this->config['replicas'] as $name => $config) {
            $weight = $config['weight'] ?? 1;
            $this->weightedConnections[$name] = $weight;
        }

        if ($this->includePrimary) {
            $weight = $this->config['weight'] ?? 1;
            $this->weightedConnections['primary'] = $weight;
        }
    }

    /**
     * Create a PDO connection.
     *
     * @param $name
     * @return \PDO
     */
    public function getPDOConnection($name)
    {
        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }

        if ($name === 'primary') {
            $dbConfig = $this->config;
        } else {
            $dbConfig = $this->config['replicas'][$name];
        }

        if (!$dbConfig) {
            throw new \RuntimeException("Connection '$name' not found");
        }

        $password = $dbConfig['password'] ?? '';
        $connectionString = self::buildConnectionString($dbConfig);

        try {
            $conn = new \PDO(
                'mysql:' . $connectionString,
                $dbConfig['username'],
                $password
            );

            $conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $conn->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

            // Reset sql_mode "STRICT_TRANS_TABLES" that will be default in MySQL 5.6
            $conn->exec('SET @@session.sql_mode = ""');
            $conn->exec('SET @@session.sql_mode = ""');

            // set encoding to utf8
            $conn->exec('SET NAMES utf8');
        } catch (\PDOException $e) {
            $message = $e->getMessage();
            $message = str_replace(
                [
                    $dbConfig['username'],
                    $dbConfig['password'],
                ],
                '******',
                $message
            );

            throw new \RuntimeException(
                'Could not connect to database. Message from SQL Server: ' . $message,
                $e->getCode()
            );
        }

        $this->connections[$name] = $conn;

        return $conn;
    }

    /**
     * @param array $dbConfig
     * @return string
     */
    private static function buildConnectionString(array $dbConfig)
    {
        if (!isset($dbConfig['host']) || empty($dbConfig['host'])) {
            $dbConfig['host'] = 'localhost';
        }

        $connectionSettings = [
            'host=' . $dbConfig['host'],
        ];

        if (!empty($dbConfig['socket'])) {
            $connectionSettings[] = 'unix_socket=' . $dbConfig['socket'];
        }

        if (!empty($dbConfig['port'])) {
            $connectionSettings[] = 'port=' . $dbConfig['port'];
        }

        if (!empty($dbConfig['charset'])) {
            $connectionSettings[] = 'charset=' . $dbConfig['charset'];
        }

        if (!empty($dbConfig['dbname'])) {
            $connectionSettings[] = 'dbname=' . $dbConfig['dbname'];
        }

        return implode(';', $connectionSettings);
    }
}
