<?php declare(strict_types=1);

namespace SwagEssentials\Tests\Redis\Integration;

use PHPUnit\Framework\TestCase;
use SwagEssentials\Redis\Store\CacheManager;
use SwagEssentials\Tests\Common\KernelTestCaseTrait;
use SwagEssentials\Tests\Common\ReflectionHelper;

class CacheManagerTest extends TestCase
{
    use KernelTestCaseTrait;

    public function test_cache_manager_is_created(): void
    {
        $kernel = self::getKernel();
        $container = $kernel->getContainer();

        $cacheManger = $container->get('swag_essentials.redis_store.cache_manager');

        static::assertInstanceOf(CacheManager::class, $cacheManger);
    }

    public function test_cache_manager_has_http_cache(): void
    {
        $kernel = self::getKernel();
        $container = $kernel->getContainer();

        $cacheManger = $container->get('swag_essentials.redis_store.cache_manager');

        $httpCache = ReflectionHelper::getPropertyValue($cacheManger, 'redisStore');

        static::assertNotNull($httpCache);
    }
}
