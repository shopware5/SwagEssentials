#!/usr/bin/env bash
#DESCRIPTION: enable essentials module --module

php -r "require_once __DIR__(sic!).'/dev-ops/common/ConfigHelper.php'; ConfigHelper::enableSwagEssentialsModule('__MODULE__');"