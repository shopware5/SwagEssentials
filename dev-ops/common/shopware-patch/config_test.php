<?php

require_once __DIR__ . '/custom/plugins/SwagEssentials/Redis/Store/RedisStore.php';
require_once __DIR__ . '/custom/plugins/SwagEssentials/Redis/Factory.php';
require_once __DIR__ . '/custom/plugins/SwagEssentials/Redis/RedisConnection.php';
require_once __DIR__ . '/custom/plugins/SwagEssentials/PrimaryReplica/PdoFactory.php';

$unitTestEnabled = false;
$csrfProtection = true;
$sessionLocking = true;

if (getenv('SHOPWARE_ENV') === 'test') {
    $csrfProtection = false;
    $sessionLocking = false;
    $unitTestEnabled = true;
}

return array (
    'db' => [
        'host' => 'mysql_master',
        'port' => '3306',
        'username' => 'root',
        'password' => 'root',
        'dbname' => 'shopware-test',
        'factory' => 'SwagEssentials\\PrimaryReplica\\PdoFactory',
        'replicas' =>
            [
                'replica-slave' =>
                    [
                        'pdoOptions' => NULL,
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
        'throwOnRecoverableError' => true,
        'ignoredExceptionClasses' => [],
    ],

    'session' => [
        'locking' => $sessionLocking,
        'unitTestEnabled' => $unitTestEnabled,
    ],

    'front' => [
        'noErrorHandler' => true,
        'throwExceptions' => true,
        'disableOutputBuffering' => true,
        'showException' => true,
    ],

    'model' => [
        'cacheProvider' => 'array',
    ],

    'phpsettings' => [
        'error_reporting' => E_ALL,
        'display_errors' => 1,
    ],

    'csrfprotection' => [
        'frontend' => $csrfProtection,
        'backend' => $csrfProtection,
    ],

    'swag_essentials' =>
        [
            'modules' =>
                [
                    'CacheMultiplexer' => true,
                    'Caching' => true,
                    'PrimaryReplica' => true,
                    'RedisNumberRange' => true,
                    'RedisPluginConfig' => true,
                    'RedisProductGateway' => true,
                    'RedisStore' => true,
                    'RedisTranslation' => true,
                ],
            'redis' =>
                [
                    0 =>
                        [
                            'host' => 'app_redis',
                            'port' => 6379,
                            'persistent' => true,
                            'dbindex' => 0,
                            'auth' => 'app',
                        ],
                ],
            'cache_multiplexer_hosts' =>
                [

                    [
                        'host' => 'http://10.123.123.31/api',
                        'user' => 'demo',
                        'password' => 'demo',
                    ],
                    [
                        'host' => 'http://10.123.123.32/api',
                        'user' => 'demo',
                        'password' => 'demo',
                    ],
                ],
            'caching_enable_urls' => true,
            'caching_enable_list_product' => true,
            'caching_enable_product' => true,
            'caching_ttl_urls' => 3600,
            'caching_ttl_list_product' => 3600,
            'caching_ttl_product' => 3600,
            'caching_ttl_plugin_config' => 3600,
            'caching_ttl_translation' => 3600,
        ],
    'httpcache' =>
        [
            'storeClass' => 'SwagEssentials\\Redis\\Store\\RedisStore',
            'keyPrefix'  => '', //this is only needed when running multiple shops on one Redis-Cluster
            'redisConnections' =>
                [
                    0 =>
                        [
                            'host' => 'app_redis',
                            'port' => 6379,
                            'persistent' => true,
                            'dbindex' => 0,
                            'auth' => 'app',
                        ],
                ],
        ]
);
