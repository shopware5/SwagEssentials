#!/usr/bin/env bash
#DESCRIPTION: disable essentials module --module

php -r "require_once __DIR__(sic!).'/_dev-ops/common/ConfigHelper.php'; ConfigHelper::disableSwagEssentialsModule('__MODULE__');"