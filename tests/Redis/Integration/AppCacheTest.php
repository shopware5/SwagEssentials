<?php

declare(strict_types=1);

namespace SwagEssentials\Tests\Redis\Integration;

use PHPUnit\Framework\TestCase;
use Shopware\Components\HttpCache\AppCache;
use Shopware\Kernel;
use SwagEssentials\Redis\Store\RedisStore;

class AppCacheTest extends TestCase
{
    private function getOptions(): array
    {
        return [
            'storeClass' => RedisStore::class,
            'compressionLevel' => 9,
            'redisConnections' => [[
                'host' => 'app_redis',
                'port' => 6379,
                'persistent' => true,
                'dbindex' => 0,
                'auth' => 'app',
            ]],
        ];
    }

    public function testAppCacheCreationWorksWithRedisStore(): void
    {
        $kernelMock = $this->getMockBuilder(Kernel::class)->disableOriginalConstructor()->getMock();

        $appCache = new AppCache(
            $kernelMock,
            $this->getOptions()
        );

        static::assertNotNull($appCache);
    }
}
