# CacheMultiplexer
**What it does**: Multiplexes cache invalidation (e.g. from the cache/performance module) to multiple instances of shopware.

**Needed for**: Cluster setups, where you need to invalidate multiple appservers at once

## Setting it up:
### Enabling it
In order to enable the submodule, import it in your `config.php`:

```php
'db' => [...],
'swag_essentials' =>
        [
            'modules' =>
                [
                    ...,
                    'CacheMultiplexer' => true,
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
        ],
```

### Configuration
The following example will configure one appserver. Credentials are credentials for the shopware API

```php
'cache_multiplexer_hosts' =>
    [
        [
            'host' => 'http://10.123.123.31/api',
            'user' => 'demo',
            'password' => 'demo',

        ],
    ],
```
