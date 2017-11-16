# Caching
**What it does**: Allows you to cache the ListProduct Structs from Shopware in Redis

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
                'RedisProductGateway' => true,
            ],        
        ...
    ],
```
