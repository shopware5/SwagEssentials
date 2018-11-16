# RedisHttpCaching
**What it does**: Allows you to store the HTTP Cache in Redis

**Needed for**: Cluster setups and setups with high load

## Setting it up:
### Enabling it
In order to enable the submodule, import it in your `config.php`:

```php
require_once __DIR__ . '/custom/plugins/SwagEssentials/Redis/Store/RedisStore.php';
require_once __DIR__ . '/custom/plugins/SwagEssentials/Redis/Factory.php';
return [
    'db' => [...],
    'swag_essentials' =>
        [
            'modules' =>
                [
                    ...
                    'RedisStore' => true,
                ],
            'redis' =>
                [
                    0 =>
                        [
                            'host' => 'app_redis_1',
                            'port' => 6379,
                            'persistent' => true,
                            'dbindex' => 0,
                            'auth' => 'app',
                        ],
                    1 =>
                        [
                            'host' => 'app_redis_2',
                            'port' => 6379,
                            'persistent' => true,
                            'dbindex' => 0,
                            'auth' => 'app',
                        ],
                        
                ],
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
                    1 =>
                        [
                            'host' => 'app_redis',
                            'port' => 6379,
                            'persistent' => true,
                            'dbindex' => 0,
                            'auth' => 'app',
                        ],                        
                ],
        ],
];
```

### Enabling Redis Cluster


```php
require_once __DIR__ . '/custom/plugins/SwagEssentials/Redis/Store/RedisStore.php';
require_once __DIR__ . '/custom/plugins/SwagEssentials/Redis/Factory.php';
return [
    'db' => [...],
    'swag_essentials' =>
        [
            'modules' =>
                [
                    ...
                    'RedisStore' => true,
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
        ],
];

### Check Redis Connection 
If you want to check the redis connection before you use it for the Store you can do it easily with a small config tweak:

```php
require_once __DIR__ . '/custom/plugins/SwagEssentials/Redis/Store/RedisStore.php';
require_once __DIR__ . '/custom/plugins/SwagEssentials/Redis/Factory.php';
$config = [
    ...
];

try {
    $testConnection = $config['httpcache']['redisConnections'];
    foreach ($testConnection as &$redisConnection) {
        $redisConnection['persistent'] = false;
    }
    unset($redisConnection);
    $redis = \SwagEssentials\Redis\Factory::factory($testConnection);
    $redis->close();
} catch (Exception $e) {
    unset($config['httpcache']);
    $config['swag_essentials']['modules']['RedisStore'] = false;
}

return $config;
```

If no redis connection is available it will automatically disable the RedisStore!