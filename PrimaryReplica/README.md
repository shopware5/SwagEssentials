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
