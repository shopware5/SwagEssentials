# Caching
**What it does**: Allows you to cache the Shopware Plugin Configuration

**Needed for**: Uncached pages, Shopware instances without HTTP cache

## Setting it up:
### Enabling it
In order to enable the submodule, import it in your `config.php`:

```php
'db' =>[...],
'swag_essentials' =>
    [
        'modules' =>
            [
                ...
                'RedisPluginConfig' => true,
            ],
        ...
        'caching_ttl_plugin_config' => 3600,        
    ],
```

### Configuration
You can configure the TTL (caching time) for this module:

```php
    'caching_ttl_plugin_config' => 3600,
```
