<?php declare(strict_types=1);

namespace SwagEssentials\PrimaryReplica;

require_once __DIR__ . '/PdoDecorator.php';
require_once __DIR__ . '/ConnectionDecision.php';
require_once __DIR__ . '/ConnectionPool.php';

class PdoFactory
{
    public static $pdoDecorator;

    public static $connectionDecision;

    public static $connectionPool;

    private static function createServices($config): PdoDecorator
    {
        if (self::$pdoDecorator) {
            return self::$pdoDecorator;
        }

        self::$connectionPool = new ConnectionPool(
            $config,
            $config['includePrimary'] ?? false,
            $config['stickyConnection'] ?? true
        );

        self::$connectionDecision = new ConnectionDecision(
            self::$connectionPool,
            $config
        );

        self::$pdoDecorator = new PdoDecorator(
            self::$connectionDecision,
            self::$connectionPool
        );

        return self::$pdoDecorator;
    }

    public static function createPDO(array $dbConfig)
    {
        self::createServices($dbConfig);

        return self::$pdoDecorator;
    }
}
