# Primary / replica
**What it does**: Use multiple databases for shopware. Will split write queries to primary connection and read queries to replica connections.

**Needed for**: Cluster setups and setups with high load on the primary database connection

## Setting it up
### Enabling it
Install the SwagEssentials Plugin and enable `PrimaryReplica` in your service.xml. Then just enable the primary/replica setup in your `config.php` in two steps:

 1. `require_once __DIR__ . '/custom/plugins/SwagEssentials/PrimaryReplica/PdoFactory.php'`;
 2. Add `'factory' => '\SwagEssentials\PrimaryReplica\PdoFactory',` to the `db` array
 3. configure at least one replica database in the `db.replicas` array


The result could look like this:


```
<?php

require_once __DIR__ . '/custom/plugins/SwagEssentials/PrimaryReplica/PdoFactory.php'`;

return array(
    'db' => array(
        'username' => 'root',
        'password' => 'root',
        'dbname' => 'training',
        'host' => 'localhost',
        'factory' => '\SwagEssentials\PrimaryReplica\PdoFactory',
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

Furthermore you can set a `weight` for every connection (also for the primary connection). This way you can define,
how often a connection should be choosen im comparison to other connections.

### Using a proxy for replica connections
In more advanced setups, you probably don't want to maintain a list of all database replicas in the application itself. If you have some sort of load balancer / proxy for your database replicas in place, you can just configure it as (the only) replica connection.
This has several advantages:

 * the proxy takes care of query distribution acrooss the replica pool
 * only the proxy needs to "know" of all replicas
 * the proxy can take care of e.g. health checks etc. 
 * solutions with haproxy or nginx are quite common 

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
