# SwagEssentials
Shopware Essentials is a tool collection for professional shopware environments:

* [Additional, low level cache layers](#caching)
* [Manage / invalidate caches of multiple appservers](#cachemultiplexer)
* [Read / write query splitting](#primary--replica)
* [Redis number ranges](#redis-numberrangeincrementer)

# Overview
## Caching
**What it does**: Allows you to cache additional resources in Shopware

**Needed for**: Uncached pages, Shopware instances without HTTP cache

[Read more](https://docs.enterprise.shopware.com/performance/essentials/#caching)

## CacheMultiplexer
**What it does**: Multiplexes cache invalidation (e.g. from the cache/performance module) to multiple instances of shopware.

**Needed for**: Cluster setups, where you need to invalidate multiple appservers at once

[Read more](https://docs.enterprise.shopware.com/performance/essentials/#cachemultiplexer)

## Primary / replica
**What it does**: Use multiple databases for shopware. Will split write queries to primary connection and read queries to replica connections.

**Needed for**: Cluster setups and setups with high load on the primary database connection

[Read more](https://docs.enterprise.shopware.com/performance/essentials/#primary-/-replica)

## Redis NumberRangeIncrementer
**What it does**: Manages Shopware's number ranges (e.g. users, orders) in Redis

**Needed for**: Setups with heavy simultaneous checkouts / registrations. Setups with high load on the database.

[Read more](https://docs.enterprise.shopware.com/performance/essentials/#redisnumberrange)

```php
'swag_essentials' => [
        'modules' => [
            // invalidate multiple appserver right from the backend
            'CacheMultiplexer' => true,
            // additional caching for shops without HTTP cache
            'Caching' => true,
            // use multiple read databases
            'PrimaryReplica' => true,
            // use NumberRanges from Redis
            'RedisNumberRange' => true,
            // use PluginConfig from Redis
            'RedisPluginConfig' => true,
            // store ListProducts in Redis
            'RedisProductGateway' => true,
            // Use Redis as HTTP cache backend
            'RedisStore' => true,
        ],
        'redis' => [
            [
                'host' => 'app_redis',
                'port' => '6379',
                'persistent' => 'true',
                'dbindex' => 0,
                'auth' => '',
            ],
        ],
        // enable/disable caches
        'caching_enable_urls' => true,
        'caching_enable_list_product' => true,
        'caching_enable_product' => true,
        'caching_enable_search' => true,
        // ttl configs
        'caching_ttl_urls' => 3600,
        'caching_ttl_list_product' => 3600,
        'caching_ttl_product' => 3600,
        'caching_ttl_search' => 3600,
    ],
```