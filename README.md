# SwagEssentials
Shopware Essentials is a tool collection for professional shopware environments:

* [Additional, low level cache layers](#caching)
* [Manage / invalidate caches of multiple appservers](#cachemultiplexer)
* [Read / write query splitting](#primary--replica)

# Caching
**What it does**: Allows you to cache additional resources in Shopware

**Needed for**: Uncached pages, Shopware instances without HTTP cache

[Read More](Caching/README.md).

# CacheMultiplexer
**What it does**: Multiplexes cache invalidation (e.g. from the cache/performance module) to multiple instances of shopware.

**Needed for**: Cluster setups, where you need to invalidate multiple appservers at once

[Read More](CacheMultiplexer/README.md).

# Primary / replica
**What it does**: Use mutiple databases for shopware. Will split write queries to primary connection and read queries to replica connections.

**Needed for**: Cluster setups and setups with high load on the primary database connection

[Read More](PrimaryReplica/README.md).

