# Caching
**What it does**: Allows you to cache additional resources in Shopware

**Needed for**: Uncached pages, Shopware instances without HTTP cache

## Setting it up:
### Enabling it
In order to enable the submodule, import it in your `service.xml`:

`<import resource="Caching/service.xml"/>`

### Configuration
Generally, you can configure the submodule for the following resources:

 * `urls`: Caching of generated SEO urls
 * `list_product`: Caching for listings
 * `product`: Caching for detail pages
 * `search`: Caching for searches

 Each of these resources can be enabled / disabled separately:

```
<!-- Enable / disable caches -->
<parameter key="swag_essentials.caching_enable_urls">1</parameter>
<parameter key="swag_essentials.caching_enable_list_product">1</parameter>
<parameter key="swag_essentials.caching_enable_product">1</parameter>
<parameter key="swag_essentials.caching_enable_search">1</parameter>
```

Also each of these resouces can have an individual TTL (caching time):

```
<!-- TTL configs -->
<parameter key="swag_essentials.caching_ttl_urls">3600</parameter>
<parameter key="swag_essentials.caching_ttl_list_product">3600</parameter>
<parameter key="swag_essentials.caching_ttl_product">3600</parameter>
<parameter key="swag_essentials.caching_ttl_search">3600</parameter>
```
