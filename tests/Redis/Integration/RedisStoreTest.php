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
use SwagEssentials\Redis\Factory;
use SwagEssentials\Redis\RedisConnection;
use SwagEssentials\Redis\Store\RedisStore;
use SwagEssentials\Tests\Common\KernelTestCaseTrait;

class RedisStoreTest extends TestCase
{
    use KernelTestCaseTrait;

    private function getRedisStoreRedisConnection(): RedisConnection
    {
        $params = self::getKernel()->getContainer()->getParameter('shopware.httpcache.redisConnections');
        static::assertIsArray($params);

        return Factory::factory($params);
    }

    private function getRedisStore(): RedisStore
    {
        $params = static::getKernel()->getContainer()->getParameter('shopware.httpcache');

        return new RedisStore($params);
    }

    public function testPurgeAllWorks(): void
    {
        $connection = $this->getRedisStoreRedisConnection();
        $redisStore = $this->getRedisStore();

        $connection->hSet(RedisStore::META_KEY, 'foo', 'bar');
        $connection->hSet(RedisStore::CACHE_KEY . '-a', 'foo', 'bar');
        $connection->set(RedisStore::CACHE_SIZE_KEY, 1000, ['ex']);
        $connection->hSet(RedisStore::ID_KEY, 'foo', 'bar');
        $connection->hSet(RedisStore::LOCK_KEY, 'foo', 1);
        static::assertEquals('bar', $connection->hGet(RedisStore::META_KEY, 'foo'));
        static::assertEquals('bar', $connection->hGet(RedisStore::CACHE_KEY . '-a', 'foo'));
        static::assertEquals('bar', $connection->hGet(RedisStore::ID_KEY, 'foo'));
        static::assertEquals('1', $connection->hGet(RedisStore::LOCK_KEY, 'foo'));
        static::assertEquals('1000', $connection->get(RedisStore::CACHE_SIZE_KEY));

        $redisStore->purgeAll();

        static::assertFalse($connection->hGet(RedisStore::META_KEY, 'foo'));
        static::assertFalse($connection->hGet(RedisStore::CACHE_KEY . '-a', 'foo'));
        static::assertFalse($connection->hGet(RedisStore::ID_KEY, 'foo'));
        static::assertFalse($connection->hGet(RedisStore::LOCK_KEY, 'foo'));
        static::assertEquals(0, $connection->get(RedisStore::CACHE_SIZE_KEY));
    }

    public function testPurgeWorksForSpecificProducts(): void
    {
        $connection = $this->getRedisStoreRedisConnection();
        $redisStore = $this->getRedisStore();
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $request->cookies->set('currency', 'foo');
        $response = new \Enlight_Controller_Response_ResponseTestCase();
        $response->headers->set('x-shopware-cache-id', 'a10');

        $redisStore->write($request, $response);

        static::assertSame(
            array_diff([
            'sw_http_cache_body-d',
            'sw_http_cache_size',
            'sw_http_cache_ids',
            'sw_http_cache_meta',
            '___VERSION___-sw_config_core',
        ], $connection->keys('*')),
            array_diff(
                $connection->keys('*'),
            [
                'sw_http_cache_body-d',
                'sw_http_cache_size',
                'sw_http_cache_ids',
                'sw_http_cache_meta',
                '___VERSION___-sw_config_core',
            ]));

        static::assertSame(
            ['d41d8cd98f00b204e9800998ecf8427e'],
            array_keys($connection->hGetAll('sw_http_cache_body-d'))
        );

        static::assertSame(
            ['e80fb65ac70384bd8bab0358d60b7cbe96de5b2de7c095e0d8695852e9c673af'],
            array_keys($connection->hGetAll('sw_http_cache_ids'))
        );

        $redisStore->purgeByHeader('x-shopware-cache-id', 'a10');

        static::assertSame(
            [],
            array_keys($connection->hGetAll('sw_http_cache_ids'))
        );

        static::assertSame(
            [],
            array_keys($connection->hGetAll('sw_http_cache_body-d'))
        );
    }
}
