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
