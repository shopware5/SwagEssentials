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

class FixedEnlightMysqlAdapter extends \Enlight_Components_Db_Adapter_Pdo_Mysql
{
    /**
     * Begin a transaction.
     */
    protected function _beginTransaction()
    {
        $this->_connect();
        $this->dbalConnection->beginTransaction();
    }

    /**
     * Commit a transaction.
     */
    protected function _commit()
    {
        $this->_connect();
        $this->dbalConnection->commit();
    }

    /**
     * Roll-back a transaction.
     */
    protected function _rollBack()
    {
        $this->_connect();
        $this->dbalConnection->rollBack();

        $this->dbalConnection->close();
        $this->dbalConnection->connect();

        // Reset sql_mode "STRICT_TRANS_TABLES" that will be default in MySQL 5.6
        $this->dbalConnection->exec('SET @@session.sql_mode = ""');
    }

    /**
     * @param array $config
     *
     * @return self
     */
    public static function createFromDbalConnectionAndConfig(Connection $connection, $config)
    {
        $adapter = new self($config);
        $adapter->dbalConnection = $connection;

        unset($adapter->_config['username'], $adapter->_config['password']);

        return $adapter;
    }
}
