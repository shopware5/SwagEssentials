<?php

declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagEssentials\PrimaryReplica;

require_once __DIR__ . '/PdoDecorator.php';
require_once __DIR__ . '/ConnectionDecision.php';
require_once __DIR__ . '/ConnectionPool.php';

class PdoFactory
{
    /**
     * @var PdoDecorator|null
     */
    public static $pdoDecorator;

    /**
     * @var ConnectionDecision|null
     */
    public static $connectionDecision;

    /**
     * @var ConnectionPool|null
     */
    public static $connectionPool;

    /**
     * @param array<string, mixed> $config
     */
    protected static function createServices(array $config): PdoDecorator
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
            self::$connectionPool
        );

        self::$pdoDecorator = new PdoDecorator(
            self::$connectionDecision,
            self::$connectionPool
        );

        return self::$pdoDecorator;
    }

    /**
     * @param array<string, mixed> $dbConfig
     */
    public static function createPDO(array $dbConfig): PdoDecorator
    {
        return self::createServices($dbConfig);
    }
}
