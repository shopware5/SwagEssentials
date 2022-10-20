<?php

declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagEssentials\Tests\Common;

use Shopware\Components\DependencyInjection\Container;
use Shopware\Components\Session\PdoSessionHandler;

class SessionHandlerFactory
{
    /**
     * @return \SessionHandlerInterface|null
     */
    public static function createSaveHandler(Container $container)
    {
        $sessionOptions = $container->getParameter('shopware.session');
        if (isset($sessionOptions['save_handler']) && $sessionOptions['save_handler'] !== 'db') {
            return null;
        }

        $dbal = $container->get('dbal_connection');

        return new DbalSessionHandler(
            $dbal,
            [
                'db_table' => 's_core_sessions',
                'db_id_col' => 'id',
                'db_data_col' => 'data',
                'db_expiry_col' => 'expiry',
                'db_time_col' => 'modified',
                'lock_mode' => $sessionOptions['locking'] ? PdoSessionHandler::LOCK_TRANSACTIONAL : PdoSessionHandler::LOCK_NONE,
            ]
        );
    }
}
