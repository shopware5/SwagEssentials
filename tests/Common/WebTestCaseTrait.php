<?php declare(strict_types=1);

namespace SwagEssentials\Tests\Common;

use Symfony\Component\HttpKernel\Client;

trait WebTestCaseTrait
{
    use KernelTestCaseTrait;

    /**
     * @beforeClass
     */
    public static function beforeStartSetToReboot()
    {
        self::setAutoReboot();
    }

    /**
     * @before
     */
    public function enableShopwareErrorHandler()
    {
        $this->disableCommonFixtures();

        self::getKernel()->beforeWebTest();
    }

    /**
     * @after
     */
    public static function disableShopwareErrorHandler()
    {
        self::getKernel()->afterWebTest();
    }

    public static function createClient(): Client
    {
        return KernelStorage::$kernelFactory->createTestClient(self::getKernel());
    }
}
