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

```
<parameter key="swag_essentials.cache_multiplexer_hosts" type="collection">
        <parameter type="collection">
            <parameter key="host">http://localhost/53/api</parameter>
            <parameter key="user">demo</parameter>
            <parameter key="password">89e495e7941cf9e40e6980d14a16bf023ccd4c91</parameter>
        </parameter>
</parameter>
```
