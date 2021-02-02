<?php declare(strict_types=1);

namespace SwagEssentials\PrimaryReplica;

use Shopware\Components\DependencyInjection\Bridge\Db;

/**
 * Class ConnectionPool initializes and maintains all replica connection. Replica connections are created using the
 * primary connection as a template
 */
class ConnectionPool
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var \PDO[]
     */
    protected $connections = [];

    /**
     * @var bool
     */
    protected $includePrimary;

    /**
     * @var bool
     */
    protected $doStickToConnection;

    /**
     * @var array
     */
    protected $weightedConnections;

    /**
     * @var string
     */
    protected $stickyConnectionName;

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
    protected function getWeightedRandomConnection()
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
    protected function prepareWeightedConnections()
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

        unset($dbConfig['factory'], $dbConfig['replicas']);

        return $this->connections[$name] = Db::createPDO($dbConfig);
    }
}
