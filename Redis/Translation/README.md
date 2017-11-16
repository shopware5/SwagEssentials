# TranslationCaching
**What it does**: Allows you to cache the translation calls against the mysql db 

**Needed for**: Uncached pages, Shopware instances without HTTP cache, Cluster setups and setups with high load on the primary database connection

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
                'RedisTranslation' => true,
            ],
        ...
        'caching_ttl_translation' => 3600,
    ],
```

### Configuration
You can configure the TTL (caching time) for this module:

```php
    'caching_ttl_translation' => 3600,
```
