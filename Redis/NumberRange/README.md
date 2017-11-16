# NumberRange
**What it does**: Allows you to remove the s_order_number ussage via mysql

**Needed for**: Cluster setups and setups with high load on the primary database connection

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
                'NumberRange' => true,
            ],
    ],
```

### Configuration
To activate the NumberRange export the existing numbers via the following cli command:

```bash
./bin/console numberrange:sync --to-redis
``` 

To save the incrementions from redis to the database you can use this command:

```bash
/bin/console numberrange:sync --to-shopware
```
