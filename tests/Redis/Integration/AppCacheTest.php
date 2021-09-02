<?php declare(strict_types=1);

namespace SwagEssentials\Tests\Redis\Integration;

use PHPUnit\Framework\TestCase;
use Shopware\Components\HttpCache\AppCache;
use Shopware\Kernel;
use SwagEssentials\Tests\Common\KernelTestCaseTrait;

class AppCacheTest extends TestCase
{
    private function getOptions(): array
    {
        return [
            'storeClass' => 'SwagEssentials\\Redis\\Store\\RedisStore',
            'compressionLevel' => 9,
            'redisConnections' =>
                [
                    [
                        'host' => 'app_redis',
                        'port' => 6379,
                        'persistent' => true,
                        'dbindex' => 0,
                        'auth' => 'app',
                    ],
                ],
        ];
    }

    public function test_app_cache_creation_works_with_redis_store()
    {
        $kernelMock = $this->getMockBuilder(Kernel::class)->disableOriginalConstructor()->getMock();

        $appCache = new AppCache(
            $kernelMock,
            $this->getOptions()
        );

        static::assertNotNull($appCache);
    }
}
