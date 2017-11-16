# Caching
**What it does**: Allows you to cache additional resources in Shopware

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
                'Caching' => true,
            ],        
        'caching_enable_urls' => true,
        'caching_enable_list_product' => true,
        'caching_enable_product' => true,
        'caching_ttl_urls' => 3600,
        'caching_ttl_list_product' => 3600,
        'caching_ttl_product' => 3600,
        'caching_ttl_plugin_config' => 3600,
        'caching_ttl_translation' => 3600,
    ],
```

### Configuration
Generally, you can configure the submodule for the following resources:

 * `urls`: Caching of generated SEO urls
 * `list_product`: Caching for listings
 * `product`: Caching for detail pages

 Each of these resources can be enabled / disabled separately:

```php
    'caching_enable_urls' => true,
    'caching_enable_list_product' => true,
    'caching_enable_product' => true,
```

Also each of these resources can have an individual TTL (caching time):

```php
    'caching_ttl_urls' => 3600,
    'caching_ttl_list_product' => 3600,
    'caching_ttl_product' => 3600,
```
