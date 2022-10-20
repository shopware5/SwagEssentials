<?php

declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
     * @var array<int, mixed>
     */
    protected $attributes = [];

    /**
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
     * @return \PDO
     */
    public function getConnectionByName(string $name)
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
     * @return array{0: string, 1: \PDO}
     */
    public function getRandomConnection(): array
    {
        if ($this->doStickToConnection && $this->stickyConnectionName) {
            return [$this->stickyConnectionName, $this->getConnectionByName($this->stickyConnectionName)];
        }

        $name = $this->getWeightedRandomConnection();

        if ($name === null) {
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
     */
    protected function getWeightedRandomConnection(): ?string
    {
        $weightedConnections = $this->weightedConnections;

        $rand = random_int(1, (int) array_sum($weightedConnections));

        foreach ($weightedConnections as $name => $weight) {
            $rand -= $weight;
            if ($rand <= 0) {
                return $name;
            }
        }

        return null;
    }

    /**
     * Build an array of all available database connections and it weight
     */
    protected function prepareWeightedConnections(): void
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
     */
    public function getPDOConnection(string $name): \PDO
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

        $connection = Db::createPDO($dbConfig);

        foreach ($this->attributes as $attribute => $value) {
            $connection->setAttribute($attribute, $value);
        }

        return $this->connections[$name] = $connection;
    }

    /**
     * @return \PDO[]
     */
    public function getActiveConnections(): array
    {
        return $this->connections;
    }

    /**
     * @param mixed $value
     */
    public function setAttribute(int $attribute, $value): void
    {
        $this->attributes[$attribute] = $value;
    }
}
