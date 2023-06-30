<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use SwagEssentials\PrimaryReplica\PdoFactory;
use SwagEssentials\Redis\Store\RedisStore;

return [
    'db' => [
        'username' => getenv('DB_USER'),
        'password' => getenv('DB_PASSWORD'),
        'dbname' => getenv('DB_NAME'),
        'host' => getenv('DB_HOST'),
        'port' => getenv('DB_PORT'),
        'factory' => PdoFactory::class,
        'replicas' => [
            'replica-slave' => [
                'pdoOptions' => null,
                'username' => 'root',
                'charset' => 'utf8',
                'password' => 'root',
                'dbname' => 'shopware',
                'host' => 'mysql_slave',
                'port' => '',
            ],
        ],
    ],
    'errorHandler' => [
        'throwOnRecoverableError' => false,
    ],
    'front' => [
        'noErrorHandler' => true,
        'throwExceptions' => true,
    ],
    'phpsettings' => [
        'display_errors' => 1,
    ],
    'template' => [
        'forceCompile' => true,
    ],
    'httpcache' => [
        'enabled' => false,
        'debug' => true,
        'storeClass' => RedisStore::class,
        'compressionLevel' => 9,
        'redisConnections' => [
            0 =>
                [
                    'host' => 'app_redis',
                    'port' => 6379,
                    'persistent' => true,
                    'dbindex' => 0,
                    'auth' => 'app',
                ],
        ],
    ],
    'cache' => [
        'frontendOptions' => [
            'write_control' => false,
        ],
        'backend' => 'Black-Hole',
        'backendOptions' => [],
    ],
    'model' => [
        'cacheProvider' => 'Array',
    ],
    'csrfProtection' => [
        'backend' => false,
        'frontend' => false,
    ],
    'logger' => [
        'level' => \Shopware\Components\Logger::DEBUG,
    ],
    'swag_essentials' => [
        'modules' => [
            // invalidate multiple appserver right from the backend
            'CacheMultiplexer' => false,
            // additional caching for shops without HTTP cache
            'Caching' => false,
            // use multiple read databases
            'PrimaryReplica' => true,
            // use NumberRanges from Redis
            'RedisNumberRange' => false,
            // use PluginConfig from Redis
            'RedisPluginConfig' => false,
            // store ListProducts in Redis
            'RedisProductGateway' => false,
            // Use Redis as HTTP cache backend
            'RedisStore' => true,
            // Use Redis for storing cached translations
            'RedisTranslation' => false,
        ],
        'redis' =>
            [
                [
                    'host' => 'app_redis',
                    'port' => 6379,
                    'persistent' => true,
                    'dbindex' => 0,
                    'auth' => 'app',
                ],
            ],
        // enable/disable caches
        'caching_enable_urls' => true,
        'caching_enable_list_product' => true,
        'caching_enable_product' => true,
        // ttl configs
        'caching_ttl_urls' => 3600,
        'caching_ttl_list_product' => 3600,
        'caching_ttl_product' => 3600,

        // ttl config for redis cache
        'caching_ttl_plugin_config' => 3600,

        'caching_ttl_translation' => 3600,
    ],
];
