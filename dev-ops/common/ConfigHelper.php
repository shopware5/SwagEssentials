<?php declare(strict_types=1);

use SwagEssentials\PrimaryReplica\PdoFactory;
use SwagEssentials\Redis\Store\RedisStore;

if (file_exists(__DIR__ . '/../../shopware/vendor/autoload.php')) {
    include __DIR__ . '/../../shopware/vendor/autoload.php';
} elseif(file_exists(__DIR__ . '/../../../../../vendor/autoload.php')) {
    include __DIR__ . '/../../../../../vendor/autoload.php';
} else {
    throw new RuntimeException('Composer autoload file not found');
}

class ConfigHelper
{
    private const CONFIG_PATH = __DIR__ . '/../../shopware/config.php';
    private const FALLBACK_CONFIG_PATH = __DIR__ . '/../../../../../config.php';

    public static function enableElasticSearch(): void
    {
        $config = self::getConfig();

        $config['es'] = [
            'enabled' => true,
            'number_of_replicas' => 0,
            'number_of_shards' => null,
            'client' => [
                'hosts' => [
                    '10.123.123.50:9200',
                ],
            ],
        ];

        self::saveConfig($config);
    }

    public static function enableDebug(): void
    {
        $config = self::getConfig();

        $config['errorHandler'] = [
            'throwOnRecoverableError' => true,
        ];

        $config['front'] = [
            'noErrorHandler' => true,
            'throwExceptions' => true,
            'disableOutputBuffering' => true,
            'showException' => true,
        ];

        $config['model'] = [
            'cacheProvider' => 'array',
        ];

        $config['phpsettings'] = [
            'error_reporting' => E_ALL,
            'display_errors' => 1,
        ];

        self::saveConfig($config);
    }

    public static function enableCsrfProtection(): void
    {
        $config = self::getConfig();

        if (isset($config['csrfprotection'])) {
            unset($config['csrfprotection']);
        }

        self::saveConfig($config);
    }

    public static function enableSwagEssentialsModule(string $moduleName): void
    {
        $config = self::getConfig();
        if (!isset($config['swag_essentials'])) {
            $config['swag_essentials'] = [
                'modules' => [
                    // invalidate multiple appserver right from the backend
                    'CacheMultiplexer' => false,
                    // additional caching for shops without HTTP cache
                    'Caching' => false,
                    // use multiple read databases
                    'PrimaryReplica' => false,
                    // use NumberRanges from Redis
                    'RedisNumberRange' => false,
                    // use PluginConfig from Redis
                    'RedisPluginConfig' => false,
                    // store ListProducts in Redis
                    'RedisProductGateway' => false,
                    // Use Redis as HTTP cache backend
                    'RedisStore' => false,
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
            ];
        }

        if (isset($config['swag_essentials']['modules'][$moduleName])) {
            $config['swag_essentials']['modules'][$moduleName] = true;
        }

        if ($moduleName === 'RedisStore') {
            $httpCache = [
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
            ];

            if (isset($config['httpcache'])) {
                $httpCache = array_merge($config['httpcache'], $httpCache);
            }

            $config['httpcache'] = $httpCache;
        }

        if ($moduleName === 'PrimaryReplica') {
            $db = [
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
            ];

            $config['db'] = array_merge($config['db'], $db);
        }

        self::saveConfig($config);
    }

    public static function disableElasticSearch(): void
    {
        $config = self::getConfig();

        if (isset($config['es'])) {
            unset($config['es']);
        }

        self::saveConfig($config);
    }

    public static function disableDebug(): void
    {
        $config = self::getConfig();

        if (isset($config['errorHandler'])) {
            unset($config['errorHandler']);
        }

        if (isset($config['front'])) {
            unset($config['front']);
        }

        if (isset($config['model'])) {
            unset($config['model']);
        }

        if (isset($config['phpsettings'])) {
            unset($config['phpsettings']);
        }

        self::saveConfig($config);
    }

    public static function disableCsrfProtection(): void
    {
        $config = self::getConfig();

        $config['csrfprotection'] = [
            'frontend' => false,
            'backend' => false,
        ];

        self::saveConfig($config);
    }

    public static function disableSwagEssentialsModule(string $moduleName = ''): void
    {
        $config = self::getConfig();

        if (isset($config['swag_essentials']['modules'][$moduleName])) {
            $config['swag_essentials']['modules'][$moduleName] = false;
        }

        if ($moduleName === 'PrimaryReplica') {
            unset($config['db']['factory'], $config['db']['replicas']);
        }


        self::saveConfig($config);
    }

    protected static function getConfig(): array
    {
        return include self::getConfigPath();
    }

    protected static function saveConfig($config): void
    {
        $configFile = '<?php
            require_once __DIR__.\'/custom/plugins/SwagEssentials/Redis/Store/RedisStore.php\';
            require_once __DIR__.\'/custom/plugins/SwagEssentials/Redis/Factory.php\';
            require_once __DIR__.\'/custom/plugins/SwagEssentials/Redis/RedisConnection.php\';
            require_once __DIR__ . \'/custom/plugins/SwagEssentials/PrimaryReplica/PdoFactory.php\';

        return ' . var_export($config, true) . ';';

        file_put_contents(self::getConfigPath(), $configFile, LOCK_EX);
    }

    private static function getConfigPath(): string
    {
        $configPath = self::CONFIG_PATH;
        if (!is_file($configPath)) {
            $configPath = self::FALLBACK_CONFIG_PATH;
            if (!is_file($configPath)) {
                throw new RuntimeException('Shopware "config.php" file not found. Please install Shopware first!');
            }
        }

        return $configPath;
    }
}
