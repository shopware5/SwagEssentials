<?php

declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagEssentials\Tests\Redis\Integration;

use PHPUnit\Framework\TestCase;
use SwagEssentials\Redis\Store\CacheManager;
use SwagEssentials\Tests\Common\KernelTestCaseTrait;
use SwagEssentials\Tests\Common\ReflectionHelper;

class CacheManagerTest extends TestCase
{
    use KernelTestCaseTrait;

    public function testCacheManagerIsCreated(): void
    {
        $cacheManger = self::getKernel()->getContainer()->get('swag_essentials.redis_store.cache_manager');

        static::assertInstanceOf(CacheManager::class, $cacheManger);
    }

    public function testCacheManagerHasHttpCache(): void
    {
        $cacheManger = self::getKernel()->getContainer()->get('swag_essentials.redis_store.cache_manager');

        $httpCache = ReflectionHelper::getPropertyValue($cacheManger, 'redisStore');

        static::assertNotNull($httpCache);
    }
}
