#!/usr/bin/env bash
#DESCRIPTION: execute on app_webserver to provision your environment

rm -rf ./shopware
./sw.phar install:release -r __SW-VERSION__ -i ./shopware --db-host __DB_HOST__ --db-user __DB_USER__ --db-password __DB_PASSWORD__ --db-name __DB_NAME__ --shop-host __SW_HOST__
#
I: mkdir shopware/custom/plugins/SwagEssentials

ln -srf CacheMultiplexer shopware/custom/plugins/SwagEssentials/
ln -srf Caching shopware/custom/plugins/SwagEssentials/
ln -srf Common shopware/custom/plugins/SwagEssentials/
ln -srf PrimaryReplica shopware/custom/plugins/SwagEssentials/
ln -srf RedisNumberRange shopware/custom/plugins/SwagEssentials/
ln -srf RedisPluginConfig shopware/custom/plugins/SwagEssentials/
ln -srf RedisProductGateway shopware/custom/plugins/SwagEssentials/
ln -srf RedisStore shopware/custom/plugins/SwagEssentials/
ln -srf SwagEssentials.php shopware/custom/plugins/SwagEssentials/