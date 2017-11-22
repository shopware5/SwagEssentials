# Primary / replica
**What it does**: Use multiple databases for shopware. Will split write queries to primary connection and read queries to replica connections.

**Needed for**: Cluster setups and setups with high load on the primary database connection

## Setting it up
### Enabling it
Install the SwagEssentials Plugin and enable `PrimaryReplica` in your config.php and enable the primary/replica setup in two steps:

 1. `require_once __DIR__ . '/custom/plugins/SwagEssentials/PrimaryReplica/PdoFactory.php'`;
 2. Add `'factory' => '\SwagEssentials\PrimaryReplica\PdoFactory',` to the `db` array
 3. configure at least one replica database in the `db.replicas` array

The result could look like this:

```
<?php

require_once __DIR__ . '/custom/plugins/SwagEssentials/PrimaryReplica/PdoFactory.php'`;

return [
    'db' => [
        'username' => 'root',
        'password' => 'root',
        'dbname' => 'training',
        'host' => 'localhost',
        'factory' => '\SwagEssentials\PrimaryReplica\PdoFactory',
        'port' => '',
        'replicas' => [
            'replica-backup' => [
                'username' => 'root',
                'password' => 'root',
                'dbname' => 'training',
                'host' => '192.168.0.30',
                'port' => '',
            ],
            'replica-redundancy' => [
                'username' => 'root',
                'password' => 'root',
                'dbname' => 'training',
                'host' => '192.168.0.31',
                'port' => '',
            ]
        ],
        'modules' =>
            [
                ...
                'PrimaryReplica' => true,
            ],  
    ]
];
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