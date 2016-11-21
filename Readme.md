# SwagEssentials
Shopware Essentials is a tool collection for professional shopware environments:

* [Additional, low level cache layers](#caching)
* [Manage / invalidate caches of multiple appservers](#cachemultiplexer)
* [Read / write query splitting](#primary--replica)

# Caching
**What it does**: Allows you to cache additional resources in Shopware

**Needed for**: Uncached pages, Shopware instances without HTTP cache

## Setting it up:
### Enabling it
In order to enable the submodule, import it in your `service.xml`:

`<import resource="Caching/service.xml"/>`

### Configuration
Generally, you can configure the submodule for the following resources:
	
 * `urls`: Caching of generated SEO urls
 * `list_product`: Caching for listings
 * `product`: Caching for detail pages
 * `search`: Caching for searches
 * `shop`: Caching for shop entity creation
 
 Each of these resources can be enabled / disabled separately:

```
<!-- Enable / disable caches -->
<parameter key="swag_essentials.caching_enable_urls">1</parameter>
<parameter key="swag_essentials.caching_enable_list_product">1</parameter>
<parameter key="swag_essentials.caching_enable_product">1</parameter>
<parameter key="swag_essentials.caching_enable_search">1</parameter>
<parameter key="swag_essentials.caching_enable_shop">1</parameter>
```

Also each of these resouces can have an individual TTL (caching time):

```
<!-- TTL configs -->
<parameter key="swag_essentials.caching_ttl_urls">3600</parameter>
<parameter key="swag_essentials.caching_ttl_list_product">3600</parameter>
<parameter key="swag_essentials.caching_ttl_product">3600</parameter>
<parameter key="swag_essentials.caching_ttl_search">3600</parameter>
<parameter key="swag_essentials.caching_ttl_shop">300</parameter>
```

# CacheMultiplexer
**What it does**: Multiplexes cache invalidation (e.g. from the cache/performance module) to multiple instances of shopware.

**Needed for**: Cluster setups, where you need to invalidate multiple appservers at once

## Setting it up:
### Enabling it
In order to enable the submodule, import it in your `service.xml`:

`<import resource="CacheMultiplexer/service.xml"/>`

### Configuration
The following example will configure one appserver. Credentials are credentials for the shopware API

```
<parameter key="swag_essentials.cache_multiplexer_hosts" type="collection">
        <parameter type="collection">
            <parameter key="host">http://localhost/53/api</parameter>
            <parameter key="user">demo</parameter>
            <parameter key="password">89e495e7941cf9e40e6980d14a16bf023ccd4c91</parameter>
        </parameter>
</parameter>
```

**Security notice**:

Make sure, that the `service.xml` file cannot be accessed from outside, e.g. with a rule in your webserver. As an
alternative, you can store the credentials in your environment, see
http://symfony.com/doc/current/configuration/external_parameters.html#environment-variables

Environment configuration:

```
fastcgi_param SYMFONY__HOST1__HOST user;
fastcgi_param SYMFONY__HOST1__USER user;
fastcgi_param SYMFONY__HOST1__PASSWORD secret;
```

Configuration in your service.xml:

```
<parameter type="collection">
    <parameter key="host">%host1.host%</parameter>
    <parameter key="user">%host1.user%</parameter>
    <parameter key="password">%host1.password%</parameter>
</parameter>
```

# Primary / replica
**What it does**: Use mutiple databases for shopware. Will split write queries to primary connection and read queries to replica connections.

**Needed for**: Cluster setups and setups with high load on the primary database connection

## Setting it up
### Enabling it
This functionality is enabled in your `config.php` in three steps:

 1. require `ShopwareConnectionWrapper`
 2. configure `'wrapperClass' => '\Doctrine\DBAL\ShopwareConnectionWrapper'` in your `db` array
 3. configure at least one replica database in the `db.replicas` array


The result could look like this:


```
<?php

require_once __DIR__ . '/custom/plugins/SwagEssentials/PrimaryReplica/Doctrine/DBAL/ShopwareConnectionWrapper.php';

return array(
    'db' => array(
        'wrapperClass' => '\Doctrine\DBAL\ShopwareConnectionWrapper',
        'username' => 'root',
        'password' => 'root',
        'dbname' => 'training',
        'host' => 'localhost',
        'port' => '',
        'replicas' => array(
            'replica-backup' => array(
                'username' => 'root',
                'password' => 'root',
                'dbname' => 'training',
                'host' => '192.168.0.30',
                'port' => '',
            ),
            'replica-redundancy' => array(
                'username' => 'root',
                'password' => 'root',
                'dbname' => 'training',
                'host' => '192.168.0.31',
                'port' => '',
            )
        )
    )
);
```


### Additional Configuration:
In the main `db` array of your `config.php` you can set additional options:
 * `includePrimary`: Also make the primary connection part of the "read" connection pool. Default: `false`
 * `stickyConnection`: Within a request, choose one random read connection from the replica pool and stick to that connection.  If disabled, for every request a new random connection will be chosen. Default: `true`

## Testing it
For testing the primary / replica setup, I used the tutum mysql docker library.

* Checked it out from `https://github.com/tutumcloud/mysql.git `
* Spawned primary and replica SQL servers:
```
docker run -d -e MYSQL_PASS=user -e MYSQL_USER=user -e REPLICATION_MASTER=true -e REPLICATION_PASS=mypass -p 13306:3306 --name mysql_master tutum/mysql
docker run -d -e MYSQL_PASS=user -e MYSQL_USER=user -e REPLICATION_SLAVE=true -p 13307:3306 --link mysql_master:mysql tutum/mysql
```
* Wired up the `config.php` of Shopware correspondingly
* In order to test side effects more properly, you can configure your slave to replicated primary changes delayed: `change master to master_delay=20`
This way you can test primary/replica delays, that might not happen on your testing system but will most probably happen on your production system.
