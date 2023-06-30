<?php

declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagEssentials\Tests\Common;

use Enlight\Event\SubscriberInterface;
use Symfony\Component\HttpKernel\Client;

class TestFactory implements TestFactoryInterface
{
    public function bootKernel(): TestKernelInterface
    {
        $kernel = new TestKernel('test', true);
        $kernel->boot();

        \restore_error_handler();
        \restore_exception_handler();

        $kernel->getContainer()->get('events')->addSubscriber(new class() implements SubscriberInterface {
            public static function getSubscribedEvents()
            {
                return [
                    'Enlight_Bootstrap_InitResource_Db' => 'overwriteDb',
                ];
            }

            public function overwriteDb()
            {
                $container = Shopware()->Container();
                $options = $container->getParameter('shopware.db');
                $options = ['dbname' => $options['dbname'], 'username' => null, 'password' => null];
                $db = FixedEnlightMysqlAdapter::createFromDbalConnectionAndConfig($container->get('dbal_connection'), $options);

                \Zend_Db_Table_Abstract::setDefaultAdapter($db);

                return $db;
            }
        });

        $kernel->getContainer()->reset('db');

        return $kernel;
    }

    public function createTestClient(TestKernelInterface $kernel): Client
    {
        $kernel
            ->getContainer()
            ->get('dbal_connection')
            ->exec('UPDATE s_core_auth SET apiKey=123 WHERE id=1');

        return new TestClient($kernel, ['HTTP_HOST' => \parse_url(SHOP_HOST, PHP_URL_HOST)]);
    }
}
