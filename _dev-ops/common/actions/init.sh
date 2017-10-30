#!/usr/bin/env bash
#DESCRIPTION: execute on app_webserver to provision your environment

rm -rf ./shopware
./sw.phar install:release -r __SW-VERSION__ -i ./shopware --db-host __DB_HOST__ --db-user __DB_USER__ --db-password __DB_PASSWORD__ --db-name __DB_NAME__ --shop-host __SW_HOST__

I: mkdir shopware/custom/plugins/SwagEssentials

ln -srf CacheMultiplexer shopware/custom/plugins/SwagEssentials/
ln -srf Caching shopware/custom/plugins/SwagEssentials/
ln -srf Common shopware/custom/plugins/SwagEssentials/
ln -srf PrimaryReplica shopware/custom/plugins/SwagEssentials/
ln -srf Redis shopware/custom/plugins/SwagEssentials/
ln -srf SwagEssentials.php shopware/custom/plugins/SwagEssentials/

php -r "require_once __DIR__(sic!).'/_dev-ops/common/ConfigHelper.php'; ConfigHelper::enableSwagEssentialsModule(' ');"

shopware/bin/console sw:plugin:refresh

shopware/bin/console sw:plugin:install SwagEssentials
shopware/bin/console sw:plugin:activate SwagEssentials