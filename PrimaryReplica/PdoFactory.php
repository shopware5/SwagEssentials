<?php
namespace SwagEssentials\PrimaryReplica;

require_once __DIR__ . '/PdoDecorator.php';
require_once __DIR__ . '/ConnectionDecision.php';
require_once __DIR__ . '/ConnectionPool.php';

class PdoFactory
{

    public static $pdoDecorator;
    public static $connectionDecision;
    public static $connectionPool;

    private static function createServices($config)
    {
        if (self::$pdoDecorator) {
            return self::$pdoDecorator;
        }


        self::$connectionPool = new ConnectionPool(
            $config,
            isset($config['includePrimary']) ? $config['includePrimary'] : false,
            isset($config['stickyConnection']) ? $config['stickyConnection'] : true
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