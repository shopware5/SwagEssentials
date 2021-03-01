<?php declare(strict_types=1);

namespace SwagEssentials\Tests\Redis\Integration;

use PHPUnit\Framework\TestCase;
use SwagEssentials\Redis\RedisConnection;
use SwagEssentials\Redis\Store\RedisStore;
use SwagEssentials\Tests\Common\KernelTestCaseTrait;

class RedisStoreTest extends TestCase
{
    use KernelTestCaseTrait;

    private function getRedisStoreRedisConnection(): RedisConnection
    {
        return self::getKernel()->getContainer()->get('swag_essentials.redis_store.redis_client');
    }

    private function getRedisStore(): RedisStore
    {
        return self::getKernel()->getContainer()->get('swag_essentials.redis_store.redis_store');
    }

    public function test_purge_all_works()
    {
        $connection = $this->getRedisStoreRedisConnection();
        $redisStore = $this->getRedisStore();

        $connection->hSet(RedisStore::META_KEY, 'foo', 'bar');
        $connection->hSet(RedisStore::CACHE_KEY . '-a', 'foo', 'bar');
        $connection->set(RedisStore::CACHE_SIZE_KEY, 1000, ['ex']);
        $connection->hSet(RedisStore::ID_KEY, 'foo', 'bar');
        $connection->hSet(RedisStore::LOCK_KEY, 'foo', 1);
        static::assertEquals(
            'bar',
            $connection->hGet(RedisStore::META_KEY, 'foo')
        );
        static::assertEquals(
            'bar',
            $connection->hGet(RedisStore::CACHE_KEY . '-a', 'foo')
        );
        static::assertEquals(
            'bar',
            $connection->hGet(RedisStore::ID_KEY, 'foo')
        );
        static::assertEquals(
            '1',
            $connection->hGet(RedisStore::LOCK_KEY, 'foo')
        );
        static::assertEquals(
            '1000',
            $connection->get(RedisStore::CACHE_SIZE_KEY)
        );

        $redisStore->purgeAll();

        static::assertFalse(
            $connection->hGet(RedisStore::META_KEY, 'foo')
        );
        static::assertFalse(
            $connection->hGet(RedisStore::CACHE_KEY . '-a', 'foo')
        );
        static::assertFalse(
            $connection->hGet(RedisStore::ID_KEY, 'foo')
        );
        static::assertFalse(
            $connection->hGet(RedisStore::LOCK_KEY, 'foo')
        );
        static::assertEquals(
            0,
            $connection->get(RedisStore::CACHE_SIZE_KEY)
        );
    }
}