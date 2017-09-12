#!/usr/bin/env bash
#DESCRIPTION: enable debug values in config.php

php -r "require_once __DIR__(sic!).'/_dev-ops/common/ConfigHelper.php'; ConfigHelper::enableDebug();"