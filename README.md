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