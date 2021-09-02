#!/usr/bin/env bash

I: mkdir shopware/custom/plugins/SwagEssentials

ln -srf CacheMultiplexer shopware/custom/plugins/SwagEssentials/
ln -srf Caching shopware/custom/plugins/SwagEssentials/
ln -srf Common shopware/custom/plugins/SwagEssentials/
ln -srf PrimaryReplica shopware/custom/plugins/SwagEssentials/
ln -srf Redis shopware/custom/plugins/SwagEssentials/
ln -srf SwagEssentials.php shopware/custom/plugins/SwagEssentials/

php -r "require_once __DIR__(sic!).'/dev-ops/common/ConfigHelper.php'; ConfigHelper::enableSwagEssentialsModule(' ');"

shopware/bin/console sw:plugin:refresh
shopware/bin/console sw:firstrunwizard:disable

shopware/bin/console sw:plugin:install SwagEssentials
shopware/bin/console sw:plugin:activate SwagEssentials