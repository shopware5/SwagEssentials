<?php declare(strict_types=1);

namespace SwagEssentials\Tests\Common;

use Doctrine\DBAL\Connection;
use Shopware\Models\Shop\Shop;
use Symfony\Component\DependencyInjection\ContainerInterface;

trait KernelTestCaseTrait
{
    use FixtureTrait;

    /**
     * @var bool
     */
    protected static $autoReboot = false;

    public static function setAutoReboot(bool $set = true)
    {
        self::$autoReboot = $set;
    }

    public static function terminateKernel()
    {
        if (KernelStorage::has()) {
            KernelStorage::retrieve()->beforeUnset();
            KernelStorage::unset();
        }
    }

    /**
     * @before
     */
    protected function startTransactionBefore()
    {
        if (KernelStorage::has()) {
            KernelStorage::retrieve()->beforeTest();
        }

        $connection = self::getKernel()->getContainer()->get('dbal_connection');
        $connection->beginTransaction();
    }

    /**
     * @after
     */
    protected function stopTransactionAfter()
    {
        /** @var Connection $connection */
        $connection = self::getKernel()->getContainer()->get('dbal_connection');

        if ($connection->isTransactionActive()) {
            $connection->rollBack();
        }
    }

    /**
     * @before
     * @after
     */
    protected function handleAutoReboot()
    {
        if (self::$autoReboot && KernelStorage::has()) {
            self::terminateKernel();
        }
    }

    public static function getKernel(): TestKernelInterface
    {
        if (!KernelStorage::has()) {
            KernelStorage::store(KernelStorage::$kernelFactory->bootKernel());
        }

        return KernelStorage::retrieve();
    }

    public static function isShopware56(ContainerInterface $container = null): bool
    {
        if (!$container) {
            $container = self::getKernel()->getContainer();
        }

        return $container->has('shopware.components.shop_registration_service');
    }

    public static function activateShopById(int $shopId)
    {
        $container = self::getKernel()->getContainer();

        /** @var Shop $shop */
        $shop = $container
            ->get('models')
            ->getRepository(Shop::class)
            ->find($shopId);

        if (self::isShopware56($container)) {
            $container->get('shopware.components.shop_registration_service')
                ->registerResources($shop);
        } else {
            $shop->registerResources();
        }

        $container->get('shopware_storefront.context_service')->initializeShopContext();
    }
}
